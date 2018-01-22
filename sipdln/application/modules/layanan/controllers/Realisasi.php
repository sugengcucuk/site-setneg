<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Realisasi extends CI_Controller {
	function __construct(){
		parent ::__construct();
        if (!$this->is_logged_in())
        {
            $logout = $this->ion_auth->logout();
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            redirect('', 'refresh');
        }
	}
    public function is_logged_in()
    {
        $user = $this->session->userdata('user_id');
        return isset($user);
    }
	public function index(){		
		$data['theme'] 		= 'pdln';
        $data['page'] 		= 'v_realisasi';
		$data['title'] 		= 'Realisasi'; 
		$data['title_page'] = 'Realisasi';
		$data['breadcrumb'] = 'Realisasi'; 
		page_render($data);
	}
	public function realisasi_list(){
		$this->crud_ajax->init('view_monitoring_pdln','id_pdln',NULL);
        $join = array(
                        't_laporan_pdln'=>array('view_monitoring_pdln.id_pdln = t_laporan_pdln.id_pdln','left')
                    );
        $where_not = array("40","50");
        
        $field = "jenis_permohonan";
        $this->crud_ajax->setExtraWhereNotIn($field,$where_not);
        $this->crud_ajax->setJoinField($join);
        $this->crud_ajax->set_select_field('view_monitoring_pdln.id_pdln,view_monitoring_pdln.unit_fp,t_laporan_pdln.date_created,view_monitoring_pdln.no_register,view_monitoring_pdln.no_surat_fp,jenis_permohonan,status,is_final_print');
        $where = array(
                        'unit_fp'=>$this->session->user_id,
                        'status'=>11
                );
        $this->crud_ajax->setExtraWhere($where);		
        $list = $this->crud_ajax->get_datatables();
        $data = array();
		if(isset($_POST['start'])){
        	$no = $_POST['start'];
		}else{
			$no=0;
		}
        foreach ($list as $pdln) {
            $no++;
            $row = array();
			$row[] = $pdln->id_pdln;
			$row[] = $no.'.'; 
            $no_register = $pdln->no_register;
            $tgl_upload = $pdln->date_created;
            $row[] = (empty($no_register)) ?  '' : str_pad($no_register, 8, '0', STR_PAD_LEFT);
			$row[] = (empty($tgl_upload)) ? '' : day_dashboard(strtotime($tgl_upload)).' '.date("H:i:s",strtotime($tgl_upload));
			$row[] = $pdln->no_surat_fp;
            $row[] = (empty($tgl_upload)) ? '<span class="btn btn-xs btn-circle red-sunglo">Belum Dilaporkan</span>' : '<span class="btn btn-xs btn-circle green-sharp">Sudah Dilaporkan</span>';
			$row[] = '<span class="label label-'.setLabel($pdln->jenis_permohonan).'">'.setJenisPermohonan($pdln->jenis_permohonan).'</span>';			
			$row[] = '<span class="label label-'.setLabelPdln($pdln->status).'">'.setStatusPdln($pdln->status).'</span>';			
			$row[] = (empty($pdln->is_final_print)) ? '<a class="btn btn-circle btn-xs purple-sharp" title="Laporkan" href="'.base_url().'layanan/realisasi/add"><i class="fa fa-arrow-circle-up"></i> </a>':'<button class="btn btn-circle btn-xs purple-sharp" title="Sudah Dilaporkan" href="'.base_url().'layanan/realisasi/add" disabled><i class="fa fa-arrow-circle-up"></i> </button>';
			$data[] = $row;
        }
        $output = array(
                        "draw" => (isset($_POST['draw']) ? $_POST['draw']:null),
                        "recordsTotal" => $this->crud_ajax->count_all(),
                        "recordsFiltered" => $this->crud_ajax->count_filtered(),
                        "data" => $data,
        );
        echo json_encode($output);
	}
	public function add(){		
		$data['theme'] 		= 'pdln';
        $data['page'] 		= 'v_add_realisasi';
		$data['title'] 		= 'Laporan Penugasan';
		$data['title_page'] = 'Laporan Penugasan';
		$data['breadcrumb'] = 'Laporan Penugasan';
		page_render($data);
	}
	public function cari_surat(){
		$id_surat = $this->input->post('ID');
    	$response = array();
    	if(strlen($id_surat)<8 || strlen($id_surat)>8){ 
			$response['status'] = FALSE; 
			$response['msg'] = "Masukkan 8 angka nomor registrasi";
			echo json_encode($response);exit; 
		}
		
    	$this->db->select('id_pdln,no_register');
    	$this->db->from('m_pdln');
    	$this->db->where('no_register',ltrim($id_surat,"0"));
    	
    	$query = $this->db->get();
    	if($query->num_rows() > 0){
    		$row = $query->row();
    		$response = array(		
                'ID'=>$row->id_pdln,
				'status'=>TRUE
    		);
    	}else{
    		$response['status'] = FALSE;
			$response['msg'] = "Nomor Registrasi Surat Tidak Ada";
    	}
    	echo json_encode($response);
	}
	
	public function print_permohonan($id_surat){
		
		$this->db->select('m_surat_masuk.ID,m_surat_masuk.NomorRegister,m_surat_masuk.DateCreated,m_surat_masuk.JenisKegiatan,m_surat_masuk.NomorSurat,
											m_surat_masuk.TanggalSurat,m_surat_masuk.BagianPemroses,m_surat_masuk.Hal,r_institution.Nama as NamaAsalInstitusi,m_unit_kerja_institusi.Name as NamaAsalUnitKerja,m_user.Nama as Pemroses,m_surat_masuk.create_date');
		$this->db->join('m_unit_kerja_institusi', 'm_unit_kerja_institusi.ID = m_surat_masuk.IDUnitKerjaAsal','left');
		$this->db->join('r_institution', 'r_institution.ID = m_surat_masuk.IDInstansiAsal','left');
		$this->db->join('m_user', 'm_user.UserID = m_surat_masuk.UserCreated','left');
		$this->db->where('m_surat_masuk.ID',$id_surat);
        $result_data = $this->db->get('m_surat_masuk')->row();

        // ----------------------------------------------------------------------
        // checking existing file
        // ----------------------------------------------------------------------
        $hash_word = md5($result_data->nomor_register . $result_data->id_jenis_kegiatan . $result_data->nomor_surat . $result_data->hal . $result_data->dokumen);

        $base_path = $this->config->item('pdln_upload_path');
        $create_date = $result_data->create_date;

        $month = date('m', $create_date);
        $year = date('Y', $create_date);

        $additional_path = $year . '/' . $month . '/umum/surat_masuk/' . $id_surat . "/";
        $targetPath = $base_path . $additional_path;

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, TRUE);
        }

        $filename = "realisasi_permohonan_sm_{$id_surat}_{$hash_word}.pdf";
        $fullpath = "{$targetPath}{$filename}";

        if (file_exists($fullpath)){
            send_file_to_browser($fullpath); // this function will exec die() and exit
        }
        // ----------------------------------------------------------------------        
		
		$this->db->select('ID,Nama');
		$jenis_kegiatan = $this->db->get('r_jenis_kegiatan')->result();
		
		$data = array(  
                        'title'=>"TANDA TERIMA BERKAS",
                        'data_resi'=>$result_data,
						'jenis_kegiatan'=>$jenis_kegiatan,
                        'm_pdf'=>$this->load->library('M_pdf'));
        $html = $this->load->view('layanan/v_print_permohonan',$data,TRUE);
		
		$data = array(  
                        'title'=>"TANDA TERIMA BERKAS",
                        'data_resi'=>$result_data,
						'jenis_kegiatan'=>$jenis_kegiatan,
                        'm_pdf'=>$this->load->library('M_pdf'));
        $html_lampiran = $this->load->view('layanan/v_print_lampiran',$data,TRUE);

		
        $this->load->library('M_pdf');
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                                    '', '', '', '',
                                    15, // margin_left
                                    15, // margin right
                                    15, // margin top
                                    10, // margin bottom
                                    18, // margin header
                                    5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html); 
        
		$this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait
                                    '', '', '', '',
                                    15, // margin_left
                                    15, // margin right
                                    15, // margin top
                                    10, // margin bottom
                                    18, // margin header
                                    5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html_lampiran); 
         
		//$filename = 'resi_sm_'.$id_surat.'_'.date('d_m_Y');
        //$this->m_pdf->pdf->Output($filename.'.pdf','I');
        $this->m_pdf->pdf->Output($fullpath, 'F');
                
        send_file_to_browser($fullpath); // this function will exec die() and exit
    }
	public function upload_laporan(){
		$this->do_upload_laporan($this->input->post('id_pdln'),$this->input->post('biaya')); 
	}
	public function do_upload_laporan($id_surat,$biaya){
		$temporary = explode(".", $_FILES["file_laporan_kegiatan"]["name"]);
		$file_extension = end($temporary);
		$new_name = $id_surat.'_'.$this->session->user_id.'_laporan_pdln.'.$file_extension;
		
		$this->db->where('id_pdln',$id_surat);
		// Berdasarkan tanggal first submit / create date insert row
		$create_date = $this->db->get('m_pdln')->row()->create_date;

		if(upload_pdln("laporan",$id_surat,$new_name,'file_laporan_kegiatan',date("Y-m-d",$create_date)))
		{
			$this->crud_ajax->init('t_laporan_pdln','id_pdln',null);
			$data_save_laporan = array(
				'id_pdln' => $id_surat,
				'dokumen' => $new_name,
				'date_created' => date('Y-m-d H:i:s'),
				'user_created' => $this->session->userdata('user_id'),
				'biaya'=>$biaya
			);            
			
			if(!$this->isExist_Laporan($id_surat)){
				$where = array('id_pdln'=>$id_surat);
				$this->crud_ajax->update($where,$data_save_laporan);
                $this->crud_ajax->init('m_pdln','id_pdln',null);
                $data_save_pdln = array(
                    'update_date' => strtotime(date("Y-m-d")),
                    'is_final_print'=>1
                );
                $this->crud_ajax->update($where,$data_save_pdln);
			}else{
				$this->crud_ajax->save($data_save_laporan);
			}
			
			$response['status'] = TRUE;
			$response['msg'] = 'Dokumen laporan realisasi berhasil diupload';
			
			echo json_encode($response);
			exit;
		}else{ 
			$response['status'] = FALSE;
			$response['msg'] = 'Terdapat kesalahan ketika upload dokumen, silahkan ulangi kembali';
			echo json_encode($response);
			exit;
		}
    }
	
	private function isExist_Laporan($id_surat){
    	$this->db->where('id_pdln',$id_surat);
    	$query = $this->db->get('t_laporan_pdln');
    	$result = true;
    	if($query->num_rows() > 0){
    		$result = false;
    	} 
    	return (bool) $result;
    }	
	public function get_data_laporan(){
    	$response = array();    	
    	$ID = $this->input->post('ID');
    	$this->db->select('*');
    	$this->db->from('t_laporan_pdln');
    	$this->db->where('id_surat_keluar',$ID);
    	
    	$query = $this->db->get();
    	if($query->num_rows() > 0){
    		$row = $query->row();
    		$response = array(
                            'id_surat_keluar'=>$row->id_surat_keluar,
							'dokumen'=>$row->dokumen, 
    						'status'=>TRUE
    				);
    	}else{
    		$response['status'] = FALSE;
    	}
    	echo json_encode($response);
    }	
	public function view_laporan(){
    	$data = array('title'=>"Dokumen"); 
        $this->load->view('v_view_dokumen',$data,TRUE);
    }	
	public function list_permohonan(){
        $order = array('update_date','DESC');
        $this->crud_ajax->init('view_monitoring_pdln','id_pdln',$order);
        $where_not = array("40","50");
        
        $field = "jenis_permohonan";
        $this->crud_ajax->setExtraWhereNotIn($field,$where_not);
        $where = array(
                        'unit_fp'=>$this->session->user_id,
                        'status'=>11,
                        'is_final_print'=>NULL
                );
        $this->crud_ajax->setExtraWhere($where);

        $list = $this->crud_ajax->get_datatables();
        $data = array();
        if(isset($_POST['start'])){
            $no = $_POST['start'];
        }else{
            $no=0;
        }
        foreach ($list as $pdln) { 
            $no++;
            $row = array();
            $row[] = $pdln->id_pdln;
            $row[] = $no.'.';
            $no_register = $pdln->no_register;
            $tgl_register = $pdln->tgl_register;
            $tgl_sp = $pdln->tgl_sp;
            $row[] = (empty($no_register)) ?  '' : str_pad($no_register, 8, '0', STR_PAD_LEFT);
            $row[] = (empty($tgl_register)) ?  '' : day($tgl_register);
            $row[] = $pdln->no_sp;
            $row[] = (empty($tgl_sp)) ?  '' : day($tgl_sp);
            $row[] = ucwords($pdln->nama_jenis_kegiatan);
            $row[] = '<span class="label label-info">'.ucwords($pdln->negara).'</span>';
            $row[] = '<span class="label label-'.setLabelPdln($pdln->status).'">'.setStatusPdln($pdln->status).'</span>';
            $row[] ='<button type="button" id="btn_set_surat" title="Pilih" class="btn btn-xs blue-chambray"><i class="fa fa-share-square-o"></i> </button>';
            $data[] = $row;
        }
        $output = array(
                        "draw" => (isset($_POST['draw']) ? $_POST['draw'] : NULL),
                        "recordsTotal" => $this->crud_ajax->count_filtered(),
                        "recordsFiltered" => $this->crud_ajax->count_filtered(),
                        "data" => $data,
        );        
        echo json_encode($output);
    }
    public function get_sp_path($id_pdln){
        $query = $this->db->get_where('m_pdln',array('id_pdln'=>$id_pdln))->row();
        $created_date = $query->create_date;
        $filename = $query->path_sp;
        (empty($filename)) ? $response['status'] = FALSE : $response['status'] = TRUE;
        $response['path_sp'] = get_file_pdln("sp",date("Y-m-d",$created_date),$id_pdln,$filename);

        echo json_encode($response);
    }
}