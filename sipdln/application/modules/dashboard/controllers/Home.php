<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	function __construct(){
		parent ::__construct();
	}
	public function index(){
		$data['theme'] 		= 'pdln';
		$data['page'] 		= 'dashboard';
		$data['title'] 		= 'Dashboard';
		$data['title_page'] = 'Dashboard';
		$data['breadcrumb'] = 'Beranda';
		page_render($data);
	}
	public function list_dashboard(){
		$id_user = $this->session->user_id;
		$level = $this->db->get_where('m_user', array('UserID' => $id_user))->row()->level; 
		
		$order = array('update_date'=>'DESC');
		
		if($level==LEVEL_TEMBUSAN_PDLN){
			$this->crud_ajax->init('m_pdln','id_pdln',$order);
			$this->crud_ajax->set_select_field('m_pdln.id_pdln,m_pdln.no_register,m_pdln.tgl_register,m_pdln.tgl_sp,m_pdln.status,m_pdln.create_date as create_date,
											m_pdln.update_date,m_pdln.no_sp,m_pdln.no_surat_usulan_fp as no_surat_usulan_fp,m_pdln.path_sp as path_sp,
											m_pdln.no_surat_usulan_pemohon as no_surat_usulan_pemohon,m_pdln.jenis_permohonan,
											m_kegiatan.NamaKegiatan as nama_jenis_kegiatan,m_pdln.keterangan as catatan,m_pdln.status');
			
			$join = array('t_template_unit_tembusan'=>array('t_template_unit_tembusan.TemplateID = m_pdln.format_tembusan','inner'),
						'm_kegiatan'=>array('m_kegiatan.ID = m_pdln.id_kegiatan','left')); 
			$this->crud_ajax->setJoinField($join);
			$where = array('t_template_unit_tembusan.UserID'=>$id_user,'m_pdln.status'=>11);
			$this->crud_ajax->setExtraWhere($where);
		}else{
			$this->crud_ajax->init('view_monitoring_pdln','id_pdln',$order);
			
			if(!is_pemohon($this->session->userdata('user_id'))){//Jika user login adalah FP
				$where = array('unit_fp'=>$this->session->userdata('user_id'));
				$this->crud_ajax->setExtraWhere($where);
			}else if(is_pemohon($this->session->userdata('user_id'))){//Jika user login adalah Pemohon
				$where = array('unit_pemohon'=>$this->session->userdata('user_id'));
				$this->crud_ajax->setExtraWhere($where); 
			}
		} 
		
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
			$update_date = $pdln->update_date;
			$row[] = (empty($no_register)) ?  '' : str_pad($no_register, 8, '0', STR_PAD_LEFT);
			$row[] = (empty($tgl_register)) ?  '' : day_dashboard($tgl_register);
			$row[] = $pdln->no_sp;
			$row[] = (empty($tgl_sp)) ?  '' : day_dashboard($tgl_sp);
			$row[] = '<span class="label label-'.setLabelPdln($pdln->status).'">'.setStatusPdln($pdln->status).'</span>';
			$row[] = (empty($update_date)) ?  '' : day_dashboard($update_date);
			$row[] = $pdln->no_surat_usulan_fp;
			$row[] = $pdln->no_surat_usulan_pemohon;
			$row[] = setJenisPermohonan($pdln->jenis_permohonan);
			$row[] = $pdln->nama_jenis_kegiatan;			
			
			if($pdln->status == 12){				
				$row[] = '<button type="button" id="view_catatan" name="view_catatan" title="Show Catatan" class="btn btn-xs btn-outline btn-circle grey-mint"><i class="fa fa-eye"></i> Lihat </button>';				
			}else{
				$row[] = '';
			}
			
			if($level==LEVEL_TEMBUSAN_PDLN){
				$row[] ="";
			}else{
				if($this->ion_auth->is_allowed(25,'update')){
					if($pdln->status >= 3 && $pdln->status <= 11)
						$row[] = '<button type="button" id="edit_pdln" name="edit_pdln" title="Edit" class="btn btn-xs purple" disabled><i class="fa fa-edit"></i> </button>';
					else{
						if(is_pemohon($this->session->user_id)){
							if($pdln->status <= 2)
								$row[] = '<button type="button" id="edit_pdln" name="edit_pdln" title="Edit" class="btn btn-xs purple" disabled><i class="fa fa-edit"></i> </button>';
							else
								$row[] = '<button type="button" id="edit_pdln" name="edit_pdln" title="Edit" class="btn btn-xs purple"><i class="fa fa-edit"> </i> </button>';
						}
						else
							$row[] = '<button type="button" id="edit_pdln" name="edit_pdln" title="Edit" class="btn btn-xs purple"><i class="fa fa-edit"> </i> </button>';
					}
				}
				else
					$row[] = '';
			}
			if($pdln->status == 11){
				$row[] = '<button type="button" id="preview_sp" name="preview_sp" title="Preview SP" class="btn btn-xs red-sunglo"><i class="fa fa-search-plus"></i> </button>
						  <a href="'.get_file_pdln("sp",date("Y-m-d",$pdln->create_date),$pdln->id_pdln,$pdln->path_sp).'" title="Download SP" class="btn btn-xs blue" download><i class="fa fa-download"></i> </a>';
			}else{
				$row[] = '<button type="button" id="preview_sp" name="preview_sp" title="Preview SP" class="btn btn-xs red-sunglo disabled"><i class="fa fa-search-plus"></i> </button>
						  <button type="button" id="download_sp" name="download_sp" title="Download SP" class="btn btn-xs blue" disabled><i class="fa fa-download"></i> </button>';
			}

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
	public function get_log_catatan($id_pdln){
		$response = array();
		$result = $this->db->from('t_approval_pdln')->where('id_pdln',$id_pdln)->where('aksi','tolak')->order_by('id','asc')->get();
		if($result->num_rows() > 0) {
			$response['total_catatan'] = $result->num_rows();			
			$response['status'] = TRUE;
			$response['data'] = array();
			foreach ($result->result() as $row) {
				$data = array();
				$data['note'] = ucwords($row->note);
				$data['day_submit_catatan'] = day(date("Y-m-d",strtotime($row->submit_date)));
				$data['time_catatan'] = date("H:i:s",strtotime($row->submit_date));
				array_push($response['data'],$data);
			}
		}
		else $response['status'] = FALSE;
		echo json_encode($response);
	}
	private function _get_detail_pembiayaan($id_karegori_biaya,$id_biaya){		
		if($id_karegori_biaya=="0"){
			$this->db->select('t_ref_pembiayaan_tunggal.id_log_dana_tunggal,r_institution.Nama');
			$this->db->where('id_log_dana_tunggal',$id_biaya); 
			$this->db->join('r_institution',"r_institution.ID = t_ref_pembiayaan_tunggal.id_instansi","left");
			return $this->db->get('t_ref_pembiayaan_tunggal')->row()->Nama;
			
		}else if($id_karegori_biaya=="1"){ 
			
			$this->db->select('t_pemb.id_dana_campuran,ref_camp.by,r_jenis_pembiayaan.Description AS jenis_biaya, 
							   (CASE WHEN ref_camp.by=1 THEN t_pemb.instansi_gov WHEN ref_camp.by=2 THEN t_pemb.instansi_donor 
							   ELSE 0 END) AS id_instansi_pembiayaan',false);
			$this->db->where('t_pemb.id_dana_campuran',$id_biaya); 
			$this->db->from('t_pembiayaan_campuran t_pemb');
			$this->db->join('t_ref_pembiayaan_campuran as ref_camp',"t_pemb.id_dana_campuran = ref_camp.id_dana_campuran");
			$this->db->join('r_jenis_pembiayaan',"r_jenis_pembiayaan.ID = ref_camp.id_jenis_biaya");
			
			$pembiayaan = ""; 
			foreach ($this->db->get()->result() as $pembiaya)
			{	$id_instansi = $pembiaya->id_instansi_pembiayaan;
				if($id_instansi ==0){ 
					$pembiayaan = $pembiayaan ."- " .  $pembiaya->jenis_biaya ." : Perseorangan";
				}else{
					$pembiayaan = $pembiayaan ."- " .  $pembiaya->jenis_biaya ." : " .$this->db->select('*')->where( "ID = $id_instansi" )->get('r_institution')->row()->Nama . '<br/><br/>';
				}
			}
			
			return $pembiayaan;
			
		}
	}
	
	public function print_permohonan($id_surat){
		setlocale(LC_ALL, 'id_ID');
		 
		$this->db->select('m_pdln.id_pdln,m_pdln.id_kegiatan,m_pdln.id_level_pejabat,m_pdln.no_sp,m_pdln.tgl_sp,m_pdln.tgl_surat_usulan_fp,m_pdln.no_surat_usulan_fp,m_pdln.pejabat_sign_sp,
							m_pdln.path_sp,m_pdln.format_tembusan,m_pdln.penandatangan_persetujuan,m_pdln.barcode, m_pdln.update_date, m_pdln.create_date');
							
		$this->db->where('m_pdln.id_pdln',$id_surat); 
        $result_data = $this->db->get('m_pdln')->row();
        
        // ----------------------------------------------------------------------
        // checking existing file
        // ----------------------------------------------------------------------
        $base_path = $this->config->item('pdln_upload_path');
        $update_date = $result_data->update_date;
        $create_date = $result_data->create_date;
        $month = month(date('n', $create_date));
        $year = date('Y', $create_date);

        $additional_path = $year . '/' . $month . '/pdln/' . $id_surat . "/";
        $targetPath = $base_path . $additional_path;

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, TRUE);
        }

        $filename = "sp_pdln_{$id_surat}_{$update_date}.pdf";
        $fullpath = "{$targetPath}{$filename}";

        if (file_exists($fullpath)){
            send_file_to_browser($fullpath); // this function will exec die() and exit
        }
        // ----------------------------------------------------------------------		
		        
		$this->db->select('r_unit_tembusan.Nama');
		$this->db->join ('r_unit_tembusan','r_unit_tembusan.ID = t_template_unit_tembusan.UnitID');
		$this->db->where('TemplateID',$result_data->format_tembusan);
		$unit_tembusan =  $this->db->get('t_template_unit_tembusan')->result();
		
		$this->db->select('t_log_peserta.id_log_peserta,t_log_peserta.start_date,t_log_peserta.end_date,m_pemohon.nama,m_pemohon.nip_nrp,m_pemohon.jabatan,
							r_institution.Nama as instansi,m_pemohon.instansi_lainnya,t_log_peserta.id_kategori_biaya,t_log_peserta.id_biaya'); 
		$this->db->join ('m_pemohon','m_pemohon.id_pemohon = t_log_peserta.id_pemohon');
		$this->db->join ('r_institution','r_institution.ID = m_pemohon.id_instansi');
		$this->db->where('id_pdln',$id_surat);
		$temp_pemohon =  $this->db->get('t_log_peserta');
		
		$list_pemohon = array();
		foreach ($temp_pemohon->result() as $pemohon)
		{
			$list_pemohon[$pemohon->id_log_peserta] = $pemohon;
			$list_pemohon[$pemohon->id_log_peserta]->pembiayaan = $this->_get_detail_pembiayaan($pemohon->id_kategori_biaya,$pemohon->id_biaya); // Get the categories sub categories
		}
		 
		$this->db->select('m_kegiatan.NamaKegiatan,m_kegiatan.StartDate,m_kegiatan.EndDate,r_negara.nmnegara as Negara');
		$this->db->join('r_negara','r_negara.ID = m_kegiatan.negara','left');
		$this->db->where('m_kegiatan.ID',$result_data->id_kegiatan); 
        $kegiatan = $this->db->get('m_kegiatan')->row();
		
		$this->db->select('*'); 
		$this->db->where('m_user.UserID',$result_data->penandatangan_persetujuan); 
        $penandatangan_persetujuan = $this->db->get('m_user')->row();
		
		$label_penandatangan = $this->get_label_penandatangan($result_data->penandatangan_persetujuan,$result_data->id_level_pejabat);
		
		$data = array(  
            'title'=>"Surat Persetujuan",
            'unit_tembusan'=>$unit_tembusan, 
			'data_sp'=>$result_data,
			'kegiatan'=>$kegiatan,
			'penandatangan'=>$penandatangan_persetujuan,
			'label_penandatangan'=>$label_penandatangan,
			'm_pdf'=>$this->load->library('M_pdf'));
        $html = $this->load->view('dashboard/v_print_permohonan',$data,TRUE); 
		
		$data = array(  
            'title'=>"Surat Persetujuan",
            'data_lampiran'=>$result_data,
			'kegiatan'=>$kegiatan,
			'list_pemohon'=>$list_pemohon,
			'label_penandatangan'=>$label_penandatangan,
			'm_pdf'=>$this->load->library('M_pdf'));
        $html_lampiran = $this->load->view('dashboard/v_print_lampiran_permohonan',$data,TRUE);
		
        $this->load->library('M_pdf'); 
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                                    '', '', '', '',
                                    15, // margin_left
                                    15, // margin right
                                    15, // margin top
                                    10, // margin bottom
                                    18, // margin header
                                    5); // margin footer
		if(isset($result_data->barcode)){
			$this->m_pdf->pdf->SetHTMLFooter ('<barcode code="' . $result_data->barcode . '" type="EAN13" size="0.5" height="1.0" alt= "testing"/>');
		}
		$this->m_pdf->pdf->WriteHTML($html); 
        
		$this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait
                                    '', '', '', '',
                                    15, // margin_left
                                    15, // margin right
                                    15, // margin top
                                    10, // margin bottom
                                    18, // margin header
                                    5); // margin footer									
		if(isset($result_data->barcode)){
			$this->m_pdf->pdf->SetHTMLFooter ('<barcode code="' . $result_data->barcode . '" type="EAN13" size="0.5" height="1.0" alt= "testing"/>');
		}
		$this->m_pdf->pdf->WriteHTML($html_lampiran);  
		
		//$filename = 'sp_pdln'.$id_surat.'_'.date('d_m_Y');
        //$this->m_pdf->pdf->Output($filename.'.pdf','I'); 
		$this->m_pdf->pdf->Output($fullpath,'F'); 
        
        // update sp_path on table m_pdln
        /*$this->db->reset_query();
        $this->db->set('path_sp', $filename);
        $this->db->where('id_pdln', $id_surat);
        if (! $this->db->update('m_pdln'))
        {
            // if false
            die('Gagal mengupdate data path_sp, silahkan hubungi Administrator.');
        }*/
        
		send_file_to_browser($fullpath); // this function will exec die() and exit
    } 
	
	public function get_label_penandatangan($id_user,$level_pejabat)
	{
		$this->db->select('*'); 
		$this->db->where('m_user.UserID',$id_user); 
        $user = $this->db->get('m_user')->row();
		
		$label = "";
		
		if(isset($user->level)){
		
			if(($user->level==LEVEL_KARO)&&($level_pejabat==LEVEL_ESELON_II))
			{
				$label="a.n.	Sekretaris Kementerian Sekretariat Negara <br/>
												&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala Biro Kerja Sama Teknik Luar Negeri";
			}else if(($user->level==LEVEL_KARO)&&($level_pejabat==LEVEL_ESELON_I))
			{
				$label="Plh.	Sekretaris Kementerian Sekretariat Negara <br/>";
			}else if(($user->level==LEVEL_KABAG))
			{
				$label="a.n.	Sekretaris Kementerian Sekretariat Negara <br/>
												Plh.&nbsp;&nbsp;&nbsp;Kepala Biro Kerja Sama Teknik Luar Negeri";
			}else if(($user->level==LEVEL_SESMEN)&&($level_pejabat==LEVEL_ESELON_I))
			{
				$label="Sekretaris Kementerian Sekretariat Negara <br/>";
			}else if(($user->level==LEVEL_SESMEN)&&($level_pejabat==LEVEL_MENTERI))
			{
				$label="Plh. Menteri Sekretariat Negara <br/>";
			}else if(($user->level==LEVEL_MENSESNEG))
			{
				$label="Menteri Sekretariat Negara <br/>";
			}
		}
		return $label; 
	}
	
}