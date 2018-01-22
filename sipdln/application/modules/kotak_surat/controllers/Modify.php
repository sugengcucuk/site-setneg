<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** 
 * Class Modify.php 
 * Handle Edit Permohonan Baru PDLN Proccess
 * @package layanan 
 * @author Guntar 
 * @version 1.0.0 
 * @date_create 23/11/2016 
**/
class Modify extends CI_Controller {
	public function __construct(){
		parent ::__construct();
	}
	public function edit_wizard($id_pdln){

		//-----------------------------------------------------------------------------------------------------
        // Memastikan bahwa level pejabat tertentu atau pengguna tertentu yang dapat melakukan perubahan data
        //-----------------------------------------------------------------------------------------------------
 		$this->db->select('p.id_pdln,p.status,p.id_level_pejabat,p.author');
        $this->db->where('p.id_pdln', $id_pdln);
        $this->db->from('m_pdln p');
        $data_pdln = $this->db->get()->row();

        $id_user = $this->session->user_id;
        $data_user = $this->db->get_where('m_user', array('UserID' => $id_user))->row();
        $level = $data_user->level;

        $this->config->load('pdln', TRUE);
        $data_integrity = $this->config->item('data_integrity', 'pdln');

		$id_level_pejabat = $data_pdln->id_level_pejabat;

        if(!empty($data_pdln) 
            && $data_pdln->author != $id_user 
			&& (array_key_exists($id_level_pejabat, $data_integrity) == false || $data_integrity[$id_level_pejabat] != $level)
            //&& in_array($data['data_pdln']->status, $pdln_status) == false 
            ){

			// show_error("Anda tidak memiliki akses terhadap halaman atau data di halaman ini. ", 403, "Forbidden");
        }
        //-----------------------------------------------------------------------------------------------------

		$data['theme'] 		= 'pdln';
        $data['page'] 		= 'v_modify_permohonan_baru';
		$data['title'] 		= 'Edit Form Permohonan Baru';
		$data['title_page'] = 'Edit Form Permohonan Baru';
		$data['breadcrumb'] = 'Edit Form Permohonan Baru';

		$where = array('Status'=>'1');

		$this->crud_ajax->init('r_level_pejabat','id',NULL);
		$this->crud_ajax->setExtraWhere($where);
		$data['level_pejabat'] = $this->crud_ajax->get_data();

        $this->crud_ajax->init('r_negara','id',NULL);
        $this->crud_ajax->setExtraWhere($where);
        $data['negara']		= $this->crud_ajax->get_data();

        $this->crud_ajax->init('r_jenis_pembiayaan','ID',NULL);
        $this->crud_ajax->setExtraWhere($where);
        $data['jenis_pembiayaan']= $this->crud_ajax->get_data();

        $this->crud_ajax->init('r_institution','ID',NULL);
        $this->crud_ajax->setExtraWhere($where);
        $data['list_instansi']= $this->crud_ajax->get_data();
		
		$where['SubKategori'] = 2;
		$this->crud_ajax->init('r_jenis_kegiatan','ID',NULL);
		$this->crud_ajax->setExtraWhere($where);
        $data['jenis_kegiatan']	= $this->crud_ajax->get_data();

		if(!is_pemohon($this->session->userdata('user_id'))){//Jika user login adalah FP
			$this->get_list_pemohon();
			$data['list_pemohon'] = $this->get_list_pemohon();
		}
		$data['id_pdln'] = $id_pdln;
		$data['status_pdln'] = $data_pdln->status;
		page_render($data);		
	}
	public function get_list_peserta(){
		$id_pdln = $this->uri->segment(4);		
		$this->crud_ajax->init('t_log_peserta','id_log_peserta',NULL);
		$join = array(
						'm_pemohon'=>array('m_pemohon.id_pemohon = t_log_peserta.id_pemohon','left'),
						'r_institution'=>array('m_pemohon.id_instansi = r_institution.ID','left'),
						);		
		$this->crud_ajax->setJoinField($join);
		$this->crud_ajax->set_select_field('id_log_peserta,m_pemohon.id_pemohon,m_pemohon.id_instansi,m_pemohon.instansi_lainnya,id_kategori_biaya,id_biaya,nik,nip_nrp,m_pemohon.nama nama_peserta,jabatan,start_date,end_date,r_institution.Nama instansi');
		$where_data = array(
			 				'id_pdln'=>$id_pdln
			 				);
		$this->crud_ajax->setExtraWhere($where_data);
		
		$list = $this->crud_ajax->get_datatables();
        $data = array();
		if(isset($_POST['start'])){
        	$no = $_POST['start'];
		}else{
			$no=0;
		}
        foreach ($list as $peserta) { 
            $no++;
            $row = array();
			$row[] = $peserta->id_log_peserta;
			$row[] = $no.'.'; 
            $row[] = $peserta->nip_nrp;
            $row[] = $peserta->nik;
			$row[] = ucwords($peserta->nama_peserta);
			$row[] = ucwords($peserta->jabatan);
			$row[] = (empty($peserta->start_date) || empty($peserta->end_date)) ? '' : day(date("Y-m-d",$peserta->start_date)).' s.d '.day(date("Y-m-d",$peserta->end_date));
			$row[] = ($peserta->id_kategori_biaya == 1) ? "Campuran":"Tunggal";
			if($peserta->id_instansi == 0 OR $peserta->id_instansi == NULL){
				$instansi = $peserta->instansi_lainnya;
			}else{
				$instansi = $peserta->instansi;
			}
			$row[] = ucwords($instansi);

			$id_peserta = $peserta->id_log_peserta;			
			$result = $this->db->get_where('view_biaya_log_peserta',array("id_log_peserta"=>$id_peserta));
			if($result->num_rows() > 0){
				$biaya;
				foreach ($result->result() as $value) {
					$biaya = $value->biaya;					
				}
				$row[] = (empty($biaya)) ? '' : 'Rp. '.number_format(intval($biaya));
			}else {
				$row[] = '';
			}
			$row[] = '<button type="button" id="remove_peserta" title="Hapus" class="btn btn-xs red"><i class="fa fa-remove"></i>&nbsp; Hapus</button>'
						.'<button type="button" id="edit_peserta" title="Edit" class="btn btn-xs purple"><i class="fa fa-edit"></i>&nbsp; Edit </button>';
			$data[] = $row;
        }
        $output = array(
                        "draw" => (isset($_POST['draw']) ? $_POST['draw'] : NULL),
                        "recordsTotal" => $this->crud_ajax->count_filtered(),
                        "recordsFiltered" => $this->crud_ajax->count_filtered(),
                        "data" => $data,
                        "query"=>$this->db->last_query()
        );        
        echo json_encode($output);
	}
	public function get_list_pemohon(){
		$this->db->where('m_unit.Parent',$this->get_parent_id());
		$this->db->where('m_unit.Status','1');
		$this->db->where_in('m_unit.Group',array(1,2));//Pemohon
		$this->db->from('m_unit_kerja_institusi as m_unit');
		$this->db->join('m_user as mu','m_unit.ID = mu.unitkerja','left');

		$query = $this->db->get();
		if($query->num_rows() > 0)
			return $query->result();
		return FALSE;
	}
	public function list_pemohon(){
		$this->crud_ajax->init('view_list_pemohon','id_pemohon',NULL);
        $where = array('status'=>1);
        $this->crud_ajax->setExtraWhere($where);
        $list = $this->crud_ajax->get_datatables();
        $data = array();
        $no = $_POST['start'];

        foreach ($list as $pemohon) {
            //$tgl_lahir = $pemohon->tgl_lahir;
            $no++;
            $row = array();
            $row[] = $pemohon->id_pemohon;
            $row[] = $no.'.';            
            $row[] = $pemohon->nip_nrp;
            $row[] = $pemohon->nik;            
            $row[] = $pemohon->nama;
            $row[] = $pemohon->jabatan;
            $row[] = $pemohon->instansi;
            $row[] = $pemohon->jenis_peserta;
            // $row[] = '<span class="label label-'.setLabelStatus($pemohon->status).'">'.setStatus($pemohon->status).'</span>';
            $row[] ='<button type="button" id="btn_set_peserta" title="Pilih" class="btn btn-xs blue-chambray"><i class="fa fa-share-square-o"></i> </button>';
            $data[] = $row;
        }
        $output = array(
                        "draw" => $_POST['draw'],                      
                        "recordsTotal" => $this->crud_ajax->count_all(),
                        "recordsFiltered" => $this->crud_ajax->count_filtered(),
                        "data" => $data,
        );
        echo json_encode($output);
	}
	public function check_file_modify_1(){
		$id_pdln = $this->input->post('id_pdln');
		$this->crud_ajax->init('m_pdln','id_pdln',NULL);
		$row = $this->crud_ajax->get_by_id($id_pdln);

		$data['status'] = FALSE;
		if(!empty($row)){
			$data = array();			
			$data['surat_usulan_pemohon'] = $row->path_file_sp_pemohon;			
			$data['surat_usulan_fp'] = $row->path_file_sp_fp;			
			$data['status'] = TRUE;
		}		 	
		echo json_encode($data);
	}
	private function _upload_file_draft($id,$jenis_file,$field_file,$file,$file_ext){
		$new_name = $id.'_'.$this->session->user_id.'_'.$jenis_file.'.'.$file_ext;
		
		$this->db->where('id_pdln',$id);
		// Berdasarkan tanggal first submit / create date insert row
		$create_date = $this->db->get('m_pdln')->row()->create_date;

		upload_pdln("pdln",$id,$new_name,$file,date("Y-m-d",$create_date));

		$response = FALSE;
		$this->crud_ajax->init('m_pdln','id_pdln',NULL);
		$data_path = array(
							$field_file => $new_name,
							'update_date'=>strtotime(date("Y-m-d H:i:s")),
							'update_by'=>$this->session->user_id
							);
		$where = array(
						'id_pdln'=>$id
				);
		$this->crud_ajax->setExtraWhere($where);
		$affected_rows = $this->crud_ajax->update($where,$data_path);
		if($affected_rows > 0){
			$response = TRUE;
		}
		return (bool) $response;
	}
	public function upload_file_kegiatan(){
		$id_pdln = $this->input->post('id_pdln');		
		$name_file = $this->input->post('jenis_doc');
		$file = $this->input->post('name_attr');
		$file_ext = $this->input->post('type_file');

		$this->db->select('create_date');
		$this->db->from('m_pdln');
		$this->db->where('id_pdln',$id_pdln);
		$create_pdln_date = $this->db->get()->row()->create_date;

		$response['status'] = FALSE;
		$data_save = array(
							'id_pdln'=>$id_pdln,
							'dir_path'=>NULL,
							'id_jenis_doc'=>$this->input->post('id_jenis_doc'),
							'kategori_doc'=>$this->input->post('kategori_doc'),
							'create_date'=>strtotime(date("Y-m-d H:i:s")),
							'update_date'=>strtotime(date("Y-m-d H:i:s")),
							'author'=>$this->session->user_id,
							'is_final'=>1
						);
		$this->crud_ajax->init('m_dok_pdln','id_pdln',NULL);

		//check is already filename on db
		$check = $id_pdln.'_'.$name_file.$file_ext;

		$is_exist = $this->db->get_where('m_dok_pdln',array('dir_path'=>$check));
		if($is_exist->num_rows() > 0){
			foreach ($is_exist->result() as $row) {
				$id_doc_db = $row->id_dok_pdln;
			}
			$new_name = $id_pdln.'_'.$name_file.$file_ext;
			$data_save_update = array(							
								'dir_path'=>$new_name,
								'id_jenis_doc'=>$this->input->post('id_jenis_doc'),
								'kategori_doc'=>$this->input->post('kategori_doc'),							
								'update_date'=>strtotime(date("Y-m-d H:i:s")),
								'author'=>$this->session->user_id							
							);			
			upload_pdln("kegiatan",$id_pdln,$new_name,$file,date("Y-m-d",$create_pdln_date));
			$where_update = array('id_dok_pdln'=>$id_doc_db);
			$affected_rows = $this->crud_ajax->update($where_update,$data_save_update);
		}
		else{
			$insert_id = $this->crud_ajax->save($data_save);
			if(!empty($insert_id)){
				$response['status'] = TRUE;
				$new_name = $id_pdln.'_'.$name_file.$file_ext;
				upload_pdln("kegiatan",$id_pdln,$new_name,$file,date("Y-m-d",$create_pdln_date));
				$save = array('dir_path'=>$new_name);
				$where = array('id_dok_pdln'=>$insert_id);

				$affected_rows = $this->crud_ajax->update($where,$save);
			}
		}		
		echo json_encode($response);
	}
	public function upload_file_peserta(){
		$id_pdln = $this->input->post('id_pdln');		
		$name_file = $this->input->post('jenis_doc');
		$file = $this->input->post('name_attr');
		$file_ext = $this->input->post('type_file');
		$id_peserta = $this->input->post('id_peserta');

		$this->db->select('create_date');
		$this->db->from('m_pdln');
		$this->db->where('id_pdln',$id_pdln);
		$create_pdln_date = $this->db->get()->row()->create_date;

		$response['status'] = FALSE;
		$data_save = array(
							'id_pdln'=>$id_pdln,
							'dir_path'=>NULL,
							'id_jenis_doc'=>$this->input->post('id_jenis_doc'),
							'kategori_doc'=>$this->input->post('kategori_doc'),
							'create_date'=>strtotime(date("Y-m-d H:i:s")),
							'update_date'=>strtotime(date("Y-m-d H:i:s")),
							'author'=>$this->session->user_id,
							'is_final'=>1
						);

		$this->crud_ajax->init('m_dok_pdln','id_pdln',NULL);
		//check is already filename on db
		$check = $id_pdln.'_'.$id_peserta.'_'.$name_file.$file_ext;

		$is_exist = $this->db->get_where('m_dok_pdln',array('dir_path'=>$check));
		if($is_exist->num_rows() > 0){
			foreach ($is_exist->result() as $row) {
				$id_doc_db = $row->id_dok_pdln;
			}
			$new_name = $id_pdln.'_'.$id_peserta.'_'.$name_file.$file_ext;
			$data_save_update = array(							
								'dir_path'=>$new_name,
								'id_jenis_doc'=>$this->input->post('id_jenis_doc'),
								'kategori_doc'=>$this->input->post('kategori_doc'),							
								'update_date'=>strtotime(date("Y-m-d H:i:s")),
								'author'=>$this->session->user_id							
							);
			upload_pdln("peserta",$id_pdln,$new_name,$file,date("Y-m-d",$create_pdln_date));
			$where_update = array('id_dok_pdln'=>$id_doc_db);
			$affected_rows = $this->crud_ajax->update($where_update,$data_save_update);
		}
		else{
		
			$insert_id = $this->crud_ajax->save($data_save);
			if(!empty($insert_id)){
				$response['status'] = TRUE;
				$new_name = $id_pdln.'_'.$id_peserta.'_'.$name_file.$file_ext;
				upload_pdln("peserta",$id_pdln,$new_name,$file,date("Y-m-d",$create_pdln_date));
				$save = array('dir_path'=>$new_name);
				$where = array('id_dok_pdln'=>$insert_id);

				$affected_rows = $this->crud_ajax->update($where,$save);
			}
		}
		echo json_encode($response);
	}
	public function get_file_path(){
		$id_pdln = $this->input->post('id_pdln');
		$this->db->where('id_pdln',$id_pdln);

		$row = $this->db->get('m_pdln')->row();

		$date_created = date("Y-m-d",$row->create_date);
		$file_pemohon = $row->path_file_sp_pemohon;
		$file_fp = $row->path_file_sp_fp;

		$response['status'] = TRUE;

		$path_pemohon = get_file_pdln("pdln",$date_created,$id_pdln,$file_pemohon);
		$path_fp = get_file_pdln("pdln",$date_created,$id_pdln,$file_fp);
		if(!empty($file_pemohon)){
					$response['path_pemohon'] = $path_pemohon;
					$response['status_file_pemohon'] = TRUE;
					$response['msg'] = "Simpan data berhasil";
		}else
			$response['status_file_pemohon'] = FALSE;
		if(!empty($file_fp)){
			$response['path_focal_point'] = $path_fp;
			$response['status_file_fp'] = TRUE;
		}
		else
			$response['status_file_fp'] = FALSE;		
		echo json_encode($response);
	}
	public function get_file_kegiatan(){
		$id_jenis = $this->input->post('id_jenis');
		$id_pdln = $this->input->post('id_pdln');
		$created_date = date("Y-m-d",($this->db->get_where('m_pdln',array('id_pdln'=>$id_pdln))->row()->create_date));

		$this->db->from('view_doc_kegiatan');
		$this->db->where('id_jenis_kegiatan',$id_jenis);
		$result = $this->db->get();
		$response = array();
		if($result->num_rows() > 0) {
			$data['status'] = TRUE;
			foreach ($result->result() as $row) {				
				$data = array();
				$data['id_jenis_doc'] 		= $row->id_jenis_doc;
				$data['nama_doc'] 			= $row->nama_doc;
				$data['nama_full_doc'] 		= ucwords($row->nama_full_doc);
				$data['is_require']			= ($row->is_require == '1') ? TRUE : FALSE;
				$data['id_jenis_kegiatan'] 	= $row->id_jenis_kegiatan;
				
				$id_jenis_doc = $row->id_jenis_doc;
				$where = array(
								'id_jenis_doc' => $id_jenis_doc,
								'id_pdln'=>$id_pdln
								);
				$is_exist = $this->db->get_where('m_dok_pdln',$where);				
				if($is_exist->num_rows() > 0){
					$nama_file_doc = $is_exist->row()->dir_path;
					$path_file = get_file_pdln("kegiatan",$created_date,$id_pdln,$nama_file_doc);
					$data['path_file'] 	= $path_file;
					$data['is_exist'] 	= TRUE;
				}else{
					$data['is_exist'] 	= FALSE;
				}
				array_push($response,$data);
			}
		}
		echo json_encode($response,JSON_PRETTY_PRINT);
	}
	public function get_file_pemohon(){
		$id_jenis = $this->input->post('id_jenis');
		$this->db->from('view_doc_pemohon');
		$this->db->where('id_jenis_kegiatan',$id_jenis);		
		$result = $this->db->get();
		$response = array();
		if($result->num_rows() > 0) {
			$data['status'] = TRUE;
			foreach ($result->result() as $row) {
				$data = array();
				$data['id_jenis_doc'] 		= $row->id_jenis_doc;
				$data['nama_doc'] 			= $row->nama_doc;
				$data['is_require']			= ($row->is_require == '1') ? TRUE : FALSE;
				$data['id_jenis_kegiatan'] 	= $row->id_jenis_kegiatan;
				array_push($response,$data);
			}
		}		
		echo json_encode($response,JSON_PRETTY_PRINT);
	}
	public function get_data_permohonan_exist(){
		$id_pdln = $this->input->post('id_pdln');
		$this->crud_ajax->init('m_pdln','id_pdln',NULL);
		$row = $this->crud_ajax->get_by_id($id_pdln);

		if(!empty($row)){
			$data = array();			
			$data['surat_usulan_pemohon'] = $row->path_file_sp_pemohon;
			$data['no_surat_usulan_pemohon'] = $row->no_surat_usulan_pemohon;
			$data['tgl_surat_usulan_pemohon'] = (empty($row->tgl_surat_usulan_pemohon)) ? '' : date("d-m-Y",$row->tgl_surat_usulan_pemohon);
			$data['level_pejabat'] = $row->id_level_pejabat;
			$data['no_surat_usulan_fp'] = $row->no_surat_usulan_fp;
			$data['tgl_surat_usulan_fp'] = (empty($row->tgl_surat_usulan_fp)) ? '' : date("d-m-Y",$row->tgl_surat_usulan_fp);
			$data['surat_usulan_fp'] = $row->path_file_sp_fp;
			$data['pejabat_sign_permohonan'] = $row->pejabat_sign_sp;
			$data['user_pemohon'] = $row->unit_pemohon;
			$data['level_status'] = $row->status;
			$id_kegiatan = $row->id_kegiatan;
			$jenis_kegiatan;
			if(!empty($id_kegiatan)){
				$result_keg = $this->db->get_where('m_kegiatan',array('ID'=>$id_kegiatan));
				if($result_keg->num_rows() > 0)
					$jenis_kegiatan = $result_keg->row()->JenisKegiatan;
			}
			$data['jenis_kegiatan'] = (empty($jenis_kegiatan)) ? '' : $jenis_kegiatan;
			$data['kegiatan'] = (empty($id_kegiatan)) ? '': $id_kegiatan;			
			//Jika Reject
			if($row->status == 12){
				$this->db->where('id_pdln',$id_pdln);
				$this->db->like('aksi',"tolak");
				$this->db->order_by('id',"desc");
				$this->db->limit(1);
				$catatan = $this->db->get('t_approval_pdln')->row();
				$data['catatan_perbaikan'] = (isset($catatan)) ? $catatan->note : '';//$this->db->get('t_approval_pdln')->row()->note;				
			}
			$data['status'] = TRUE;
		}
		echo json_encode($data);
	}
	public function get_doc_kegiatan_exist(){
		$id_pdln = '';
		$this->db->where('id_pdln',$id_pdln);
		$id_kegiatan = $this->db->get('m_pdln')->row()->id_kegiatan;

		$this->db->where('ID',$id_kegiatan);
		$id_jenis_kegiatan = $this->db->get('m_kegiatan')->row()->JenisKegiatan;
		
		$this->db->from('m_dok_pdln');
		$this->db->where('id_pdln',$id_pdln);
		$this->db->where('kategori_doc','1');

		$result = $this->db->get();
		$response = array();
		if($result->num_rows() > 0){
			$data['status'] = TRUE;
			foreach ($result->result() as $row){
				$data = array();
				$data['id_jenis_doc'] 		= $row->id_jenis_doc;
				$data['id_dok_pdln'] 		= $row->id_dok_pdln;
				$data['file_name']			= $row->dir_path;
				array_push($response,$data);
			}
		}
		return $response;
	}
	public function get_parent_id(){
		$this->db->where('UserID',$this->session->userdata('user_id'));
		$parent = $this->db->get('m_user')->row()->unitkerja;
		return $parent; //unitkerja user fp sbg parent 
	}
    public function get_kegiatan(){
    	$id_jenis = $this->input->post('id_jenis');
		$where = array(
						'JenisKegiatan'=>$id_jenis,
						'Status'=>'1'
						);
		$this->crud_ajax->init('m_kegiatan','ID',NULL);
		$this->crud_ajax->setExtraWhere($where);
		$query = $this->crud_ajax->get_data();
		if(count($query) > 0) {
			foreach($query as $row) {
				echo '<option value="">--Pilih--</option>';
				echo '<option value="'.$row->ID.'">'.trim($row->NamaKegiatan).'</option>';
			}
		}else
			echo '<option value="">--Kegiatan Tidak Tersedia--</option>';
    }
	public function get_detail_keg(){
		$id_kegiatan = $this->input->post('id_keg');
		$data = array();
		$this->db->from('view_kegiatan');
		$this->db->where('id_kegiatan',$id_kegiatan);
		$result = $this->db->get();
		if($result->num_rows() > 0) {
			$data['status'] = TRUE;
			foreach ($result->result() as $row) {
				$data['penyelenggara'] 	= $row->penyelenggara;
				$data['negara'] 	= $row->nmnegara;
				$data['kota'] 		= $row->nmkota;
				$data['tgl_mulai_kegiatan'] = day($row->tgl_mulai_kegiatan);
				$data['tgl_akhir_kegiatan'] = day($row->tgl_akhir_kegiatan);
				$data['min_date'] = date("d/m/Y",strtotime($row->tgl_mulai_kegiatan));
				$data['max_date'] = date("d/m/Y",strtotime($row->tgl_akhir_kegiatan));
			}
			$data['status'] = TRUE;
		}else
			$data['status'] = FALSE;
		echo json_encode($data,JSON_PRETTY_PRINT);
	}
	public function get_data_pemohon(){
		$response = array();
		$response['status'] = TRUE;
		$id_pemohon = $this->input->post('id_pemohon');		
		$this->crud_ajax->init('view_list_pemohon','id_pemohon',NULL);
		$where = array('id_pemohon'=>$id_pemohon);
		$this->crud_ajax->setExtraWhere($where);
		$result = $this->crud_ajax->get_data();
		if(count($result) > 0){
			foreach($result as $row){
				$response['jenis_peserta'] = $row->jenis_peserta;
				$response['nip_peserta'] = $row->nip_nrp;
				$response['nik_peserta'] = $row->nik;
				$response['nama_peserta'] = $row->nama;
				$response['jabatan_peserta'] = $row->jabatan;
				$response['instansi'] = $row->instansi;
				$response['email_peserta'] = $row->email;
				$response['telp_peserta'] = $row->telp;
			}
		}
		else{
			$response['status'] = FALSE;
			$response['msg'] = "Error Getting Data";
		}				
		echo json_encode($response);
	}
	public function get_data_peserta(){
		$this->input->post('id');
	}
	public function validate_exist_going(){
		$response = array();
		$response['status'] = TRUE;

		if($result->num_rows() > 0){
			$response['status'] = FALSE;
			$response['msg'] = FALSE;
		}		
		echo json_encode($response);
	}
	public function is_pemohon(){
		$result = array();
        $result['status'] = TRUE;
        if(!is_pemohon($this->session->user_id)){
            $result['id_user_fp'] = $this->session->user_id;
            $result['id_user_pemohon'] = '';
            $result['status'] =  FALSE;
        }else{
            $result['id_user_fp'] = get_focal_point_by($this->session->user_id);
            $result['id_user_pemohon'] = $this->session->user_id;
        }
        echo json_encode($result);
	}
	public function set_no_register(){
		$no = $this->db->get('t_suratmasuk_increment')->row()->nomor;
		$this->db->where('id',1);
		$data = array(
						'nomor'=>$no+1,
						'last_update'=>strtotime(date("Y-m-d H:i:s")),
						'tahun'=>date("Y"),
						'status'=>1
					);
		$this->db->update('t_suratmasuk_increment',$data);
		return $no+1;
	}		
	public function submit_permohonan(){
		$status;
		$response['status'] = TRUE;
		$id_pdln = $this->input->post('id_pdln');
		// var_dump($id_pdln);
		
		$data_pdln = $this->db->from('m_pdln')->where('id_pdln',$id_pdln)->get()->row(); 

		//-----------------------------------------------------------------------------------------------------
        // Memastikan bahwa level pejabat tertentu atau pengguna tertentu yang dapat melakukan perubahan data
        //-----------------------------------------------------------------------------------------------------
        $id_user = $this->session->user_id;
        $data_user = $this->db->get_where('m_user', array('UserID' => $id_user))->row();
        $level = $data_user->level;

        $this->config->load('pdln', TRUE);
        $data_integrity = $this->config->item('data_integrity', 'pdln');

		$id_level_pejabat = $data_pdln->id_level_pejabat;

   //      if(!empty($data_pdln) 
   //          && $data_pdln->author != $id_user 
			// && (array_key_exists($id_level_pejabat, $data_integrity) == false || $data_integrity[$id_level_pejabat] != $level)
   //          //&& in_array($data['data_pdln']->status, $pdln_status) == false 
   //          ){

			// show_error("Anda tidak memiliki akses terhadap halaman atau data di halaman ini. ", 403, "Forbidden");
   //      }
        //-----------------------------------------------------------------------------------------------------
		
		if($data_pdln->status==12 || $data_pdln->status==0){ //Jika status dikembalikan			
			$this->db->from('t_approval_pdln');
			$this->db->where('id_pdln',$id_pdln);
			$this->db->where('aksi','tolak');
			$reject_approval = $this->db->get()->row();

					
			/*$response['status'] = False;
			$response['msg']    = $data; 
			echo json_encode($response);
			exit;*/
			
			// $this->db->where('id_pdln',$id_pdln);
			// $affected_rows = $this->db->get()->row();
						
			// $data_approval = array(
			// 						'id_pdln'=>$id_pdln,
			// 						'note'=>'',
			// 						'submit_date'=>'',
			// 						'assign_date'=>date("Y-m-d H:i:s"),
			// 						'level'=>$reject_approval->level,
			// 						'aksi'=>'',
			// 						'is_done'=>0
			// 				);
			// $insert_id_approval = $this->db->insert('t_approval_pdln',$data_approval);
			// if(empty($insert_id_approval)){
			// 	$response['msg'] = "Gagal Simpan Data Workflow";
			// 	$response['status'] = FALSE;
			// }

			// /*Update Current Data Approval*/
			$this->crud_ajax->init('t_approval_pdln','id',null);
			$data_update_approval = array(
				'user_id' => $this->session->user_id,
				'note' => 'Submit perbaikan',
				'submit_date' => date('Y-m-d H:i:s'),
				'aksi' => 'resubmit', 
				'is_done' => 1,
			);

			// $no_surat_usulan_pemohon = $this->input->post('no_surat_usulan_pemohon');
			// $tgl_surat_usulan_pemohon = $this->input->post('tgl_surat_usulan_pemohon');
			// $no_surat_usulan_fp = $this->input->post('no_surat_usulan_focal_point');
			// $tgl_surat_usulan_fp = $this->input->post('tgl_surat_usulan_fp');		
			// $pejabat_sign_sp = $this->input->post('pejabat_sign_permohonan');
			// $id_kegiatan = $this->input->post('kegiatan');
			// $data = array(						
			// 			'update_date'=>strtotime(date('Y-m-d H:i:s')),
			// 			'status'=> get_id_status_approval($reject_approval->level) ,						
			// 			'is_draft'=>0,
			// 			'pejabat_sign_sp' => $this->input->post('pejabat_sign_permohonan'),
			// 			'id_kegiatan' => $this->input->post('kegiatan'),
			// 			'no_surat_usulan_pemohon'=>(empty($no_surat_usulan_pemohon)) ? NULL : $no_surat_usulan_pemohon,
			// 			'tgl_surat_usulan_pemohon'=>(empty($tgl_surat_usulan_pemohon)) ? NULL : strtotime($tgl_surat_usulan_pemohon),
			// 			'no_surat_usulan_fp'=>(empty($no_surat_usulan_fp)) ? NULL : $no_surat_usulan_fp,
			// 			'tgl_surat_usulan_fp'=>(empty($tgl_surat_usulan_fp)) ? NULL : strtotime($tgl_surat_usulan_fp),
			// 		);
			// $where_approval = array('level' =>'Focalpoint','id_pdln' =>  $id_pdln,'is_done' => 0);
			// $affected_row_u = $this->crud_ajax->update($where_approval,$data_update_approval);



// INI kalo membuat			

			#MODUL ARSIPKAN
			$this->crud_ajax->init('m_pdln','id_pdln',NULL);
			$data_update_doc_lama  = array(
				'status'=>200
			);
			$this->db->where('id_pdln',$id_pdln);
			$insert_id_pdln_new_draft = $this->db->update('m_pdln',$data_update_doc_lama);

			$this->crud_ajax->init('m_pdln','id_pdln',NULL);
			$response['level'] = $level ;
			$response['reject_approval'] = $reject_approval ;
			if ($level == LEVEL_PEMOHON) {
				$status_resubmite = get_id_status_approval('Focalpoint');
				$mess = 'Focalpoint';
			}else if ($level == LEVEL_FOCALPOINT) {
				$mess = "SETNEG";
				$status_resubmite = get_id_status_approval($reject_approval->level);
			}else{
				$mess = "SETNEG";
				$status_resubmite = get_id_status_approval('Analis');
			}
			$data_save = array(
						'unit_pemohon'=>$data_pdln->unit_pemohon,
						'unit_fp'=>$data_pdln->unit_fp,
						'id_level_pejabat'=>$data_pdln->id_level_pejabat,
						'no_surat_usulan_pemohon'=>$data_pdln->no_surat_usulan_pemohon,
						'tgl_surat_usulan_pemohon'=>$data_pdln->tgl_surat_usulan_pemohon,
						'no_surat_usulan_fp'=>$data_pdln->no_surat_usulan_fp,
						'tgl_surat_usulan_fp'=>$data_pdln->tgl_surat_usulan_fp,
						'create_date'=>$data_pdln->create_date,
						'path_file_sp_pemohon'=>$data_pdln->path_file_sp_pemohon,
						'path_file_sp_fp'=>$data_pdln->path_file_sp_fp,
						'pejabat_sign_sp'=>$data_pdln->pejabat_sign_sp,
						'id_kegiatan'=>$data_pdln->id_kegiatan,
						'status'=>$status_resubmite,
						'jenis_permohonan'=>$data_pdln->jenis_permohonan, // Baru
						'is_draft'=>$data_pdln->is_draft,
						'path_sp'=>$data_pdln->path_sp,
						'no_register'=>$data_pdln->no_register,
						'no_sp'=>$data_pdln->no_sp,
						'tgl_register'=>$data_pdln->tgl_register,
						'tgl_sp'=>$data_pdln->tgl_sp,
						'author' => $this->session->user_id,
						'update_date'=>$data_pdln->update_date,
						'update_by'=>$data_pdln->update_by,
						'last_download'=>$data_pdln->last_download,
						'format_tembusan'=>$data_pdln->format_tembusan,
						'penandatangan_persetujuan'=>$data_pdln->penandatangan_persetujuan,
						'is_final_print'=>$data_pdln->is_final_print,
						'keterangan'=>$data_pdln->keterangan,
						'barcode'=>$data_pdln->barcode,
						'id_pdln_lama'=>$data_pdln->id_pdln
						);

			// $insert_ipdln_baru = $this->db->insert('m_pdln',$data_save);
			$insert_ipdln_baru = $this->crud_ajax->save($data_save);

			
			if(empty($insert_ipdln_baru)){ // If Failed then trans_rollback
				$response['msg'] = "Simpan data gagal, harap coba lagi setelah beberapa waktu";
				$response['status'] = FALSE;				
				exit();
			}else{
				// t_log_peserta SU
				$data_pemohon_document_lama = $this->db->from('t_log_peserta')->where('id_pdln',$id_pdln)->get()->row(); 

				$data_save_log_peserta = array (
												'id_pemohon'=>$data_pemohon_document_lama->id_pemohon,
												'id_pdln'=>$insert_ipdln_baru->id_pdln,
												'id_kategori_biaya'=>$data_pemohon_document_lama->id_kategori_biaya,												
												'id_biaya'=>$data_pemohon_document_lama->id_biaya,
												'start_date'=>$data_pemohon_document_lama->start_date,
												'end_date'=>$data_pemohon_document_lama->end_date
										);
				$this->crud_ajax->init('t_log_peserta','id_log_peserta',NULL);
				$insert_row_log_peserta_tunggal = $this->crud_ajax->save($data_save_log_peserta);
				$response['status'] = TRUE;
				$response['msg'] = $mess ;
				$response['no_register'] = $data_pdln->no_register;
				$response['data_pdln'] = $data_pdln;

			}
			
		}
		else{
			if(is_pemohon($this->session->user_id)){
				$status = 2;
				$no_register = $this->set_no_register();
				$tgl_register = strtotime(date('Y-m-d H:i:s'));
				$level = "Draft Focalpoint"; //Dicatat di tabel approval hanya sebagai log, tidak ditampilkan di history approval untuk internal
				$is_draft = 1;
			}else {
				$no_register = $this->set_no_register();
				$tgl_register = strtotime(date('Y-m-d H:i:s'));
				$status = 3;
				$level = "Analis";
				$is_draft = 0;
			}
			$data = array(
						'no_register'=>$no_register,
						'tgl_register'=>$tgl_register,
						'update_date'=>strtotime(date('Y-m-d H:i:s')),
						'status'=>$status,
						'id_kegiatan'=>$this->input->post('kegiatan'),
						'is_draft'=>$is_draft
					);
			$id_pdln = $this->input->post('id_pdln');
			$this->db->where('id_pdln',$id_pdln);
			$affected_rows = $this->db->update('m_pdln',$data);
			$data_approval = array(
									'id_pdln'=>$id_pdln,
									'user_id'=>$this->session->user_id,
									'note'=>'',
									'submit_date'=>date("Y-m-d H:i:s"),
									'assign_date'=>'',
									'level'=>$level,
									'aksi'=>'',
									'is_done'=>0
							);
			$insert_id_approval = $this->db->insert('t_approval_pdln',$data_approval);
			
			if(empty($insert_id_approval)){
				$response['msg'] = "Gagal Simpan Data Workflow";
				$response['status'] = FALSE;
			}else{
				if(is_pemohon($this->session->user_id))
					$response['msg'] = "Focal Point";
				else{
					$response['msg'] = "SETNEG";
					$response['no_register'] = str_pad($no_register, 8, '0', STR_PAD_LEFT);
				}
			}
		}
		echo json_encode($response);
	}
	public function save_draft_update(){
		if(is_pemohon($this->session->user_id)){
			$id_pemohon = $this->session->user_id;
			$fp = get_focal_point_by($this->session->user_id);
			(empty($fp)) ? $fp = NULL : $fp;
			$is_fp_null = is_null($fp) ? 'TRUE' : 'FALSE';
			log_message('error', "method=save_draft_update;is_pemohon=TRUE;session_user_id={$this->session->user_id};variable_fp={$fp};is_null={$is_fp_null}");                        
		}
		else{
			// check if user fp not select any pemohon from list select
			$id_pemohon = $this->input->post('list_pemohon');
			(empty($id_pemohon)) ? $id_pemohon = NULL : $id_pemohon;
			$fp = $this->session->user_id;
                        $is_fp_null = is_null($fp) ? 'TRUE' : 'FALSE';
                        log_message('error', "method=save_draft_update;is_pemohon=FALSE;session_user_id={$this->session->user_id};variable_fp={$fp};is_null={$is_fp_null}");                                                
		}
		// init table m_pdln
		$this->crud_ajax->init('m_pdln','id_pdln',NULL);
		$response = array();

		/****/
		$id_level_pejabat = $this->input->post('level_pejabat');
		$no_surat_usulan_pemohon = $this->input->post('no_surat_usulan_pemohon');
		$tgl_surat_usulan_pemohon = $this->input->post('tgl_surat_usulan_pemohon');
		$no_surat_usulan_fp = $this->input->post('no_surat_usulan_focal_point');
		$tgl_surat_usulan_fp = $this->input->post('tgl_surat_usulan_fp');		
		$pejabat_sign_sp = $this->input->post('pejabat_sign_permohonan');
		$id_kegiatan = $this->input->post('kegiatan');
		// Not use for insert to table 		
		// Data Update
		$data_save = array(
						'unit_pemohon'=>$id_pemohon,
						'unit_fp'=>$fp,
						'id_level_pejabat'=>(empty($id_level_pejabat)) ? NULL : $id_level_pejabat,
						'no_surat_usulan_pemohon'=>(empty($no_surat_usulan_pemohon)) ? NULL : $no_surat_usulan_pemohon,
						'tgl_surat_usulan_pemohon'=>(empty($tgl_surat_usulan_pemohon)) ? NULL : strtotime($tgl_surat_usulan_pemohon),
						'no_surat_usulan_fp'=>(empty($no_surat_usulan_fp)) ? NULL : $no_surat_usulan_fp,
						'tgl_surat_usulan_fp'=>(empty($tgl_surat_usulan_fp)) ? NULL : strtotime($tgl_surat_usulan_fp),
						'update_date'=>strtotime(date("Y-m-d H:i:s")),						
						'pejabat_sign_sp'=>(empty($pejabat_sign_sp)) ? NULL : $pejabat_sign_sp,
						'id_kegiatan'=>(empty($id_kegiatan)) ? NULL : $id_kegiatan,
						'update_by'=>$this->session->user_id
						);
		$response['status'] = TRUE;
		// Key for Updates
		$id_pdln = $this->input->post('id_pdln');
		if(empty($id_pdln)) {
			$response['msg'] = "Gagal mendapatkan Data Kode PDLN";
			$response['status'] = FALSE;
			exit();
		}else{
			$where = array('id_pdln'=>$id_pdln);
			$affected_rows = $this->crud_ajax->update($where,$data_save);
			if($affected_rows < 1){ // if failed then trans_rollback
				$response['msg'] = "Update data gagal, penyimpanan data tidak perlu dilakukan";
				$response['status'] = FALSE;
				exit();
			}else{
				//upload file_sp_pemohon			
				if(isset($_FILES['file_surat_usulan_pemohon']['type'])){
					$file_surat_usulan_pemohon = $_FILES['file_surat_usulan_pemohon'];
					$name = $_FILES["file_surat_usulan_pemohon"]["name"];
					$file_ext = strtolower(end((explode(".", $name))));
					if($this->_upload_file_draft($id_pdln,'file_surat_usulan_pemohon','path_file_sp_pemohon','file_surat_usulan_pemohon',$file_ext)){
						$response['status'] = TRUE;						
					}
					else {
						$response['msg'] = "Sukses simpan data";
						$response['status'] = FALSE;
					}
				}
				// upload file_sp_fp
				if(isset($_FILES['file_surat_usulan_fp']['type'])){
					$file_surat_usulan_fp = $_FILES['file_surat_usulan_fp'];
					$name = $_FILES["file_surat_usulan_fp"]["name"];
					$file_ext = strtolower(end((explode(".", $name))));
					if($this->_upload_file_draft($id_pdln,'file_surat_usulan_fp','path_file_sp_fp','file_surat_usulan_fp',$file_ext)){
						$response['status'] = TRUE;						
					}
					else {
						$response['msg'] = "Sukses simpan data";
						$response['status'] = FALSE;
					}
				}				
			}
			$response['id_pdln'] = $id_pdln;
		}
		echo json_encode($response);
	}
	public function add_peserta(){
		$response['status'] = TRUE;
		$response['id_pdln'] = $this->input->post('id_pdln');
                if($this->input->post('id_log_peserta') != '')
                    return $this->update_peserta();
                
		// Data Peserta atau Pemohon
		$id_peserta = $this->input->post('id_peserta');		
		//Pembiayaan 0 Tunggal 1 Campuran
		$jenis_pendanaan = $this->input->post('opt_pendanaan');
		if($jenis_pendanaan == '0'){ // Tunggal

			$instansi_tunggal = $this->input->post('instansi_tunggal');
			$biaya_tunggal = $this->input->post('biaya_tunggal');

			$data_biaya_tunggal = array(
										'id_instansi'=>(empty($instansi_tunggal)) ? NULL : $instansi_tunggal,
										'biaya'=>(empty($biaya_tunggal)) ? NULL : convert_to_number($biaya_tunggal)
								);
			$this->crud_ajax->init('t_ref_pembiayaan_tunggal','id_log_dana_tunggal',NULL);
			$insert_row_tunggal = $this->crud_ajax->save($data_biaya_tunggal);
			if(empty($insert_row_tunggal)){ // If Failed then trans_rollback
				$response['msg'] = "Simpan data pembiayaan gagal, harap coba lagi";
				$response['status'] = FALSE;
			}else{
				$data_save_log_peserta = array (
												'id_pemohon'=>$id_peserta,
												'id_pdln'=>$this->input->post('id_pdln'),
												'id_kategori_biaya'=>$jenis_pendanaan,												
												'id_biaya'=>$insert_row_tunggal,
												'start_date'=>strtotime($this->input->post('start_date_penugasan')),
												'end_date'=>strtotime($this->input->post('end_date_penugasan'))
										);
				$this->crud_ajax->init('t_log_peserta','id_log_peserta',NULL);
				$insert_row_log_peserta_tunggal = $this->crud_ajax->save($data_save_log_peserta);
			}
		}
		else if($jenis_pendanaan == '1'){ // Campuran

			$instansi_gov = $this->input->post('instansi_campuran_gov');
			$instansi_campuran_donor = $this->input->post('instansi_campuran_donor');
			$biaya_campuran = $this->input->post('biaya_campuran');

			$data_biaya_campuran = array(
										'instansi_gov'=>(empty($instansi_gov)) ? NULL : $instansi_gov,
										'instansi_donor'=>(empty($instansi_campuran_donor)) ? NULL : $instansi_campuran_donor,
										'biaya_apbn'=>(empty($biaya_campuran)) ? NULL : convert_to_number($biaya_campuran),
									);
			
			$this->crud_ajax->init('t_pembiayaan_campuran','id_dana_campuran',NULL);
			$insert_row_campuran = $this->crud_ajax->save($data_biaya_campuran);
			
			if(empty($insert_row_campuran)){ // If Failed then trans_rollback
				$response['msg'] = "Simpan data pembiayaan campuran gagal, harap coba lagi";
				$response['status'] = FALSE;
				exit();
			}else{
				//insert log peserta
				$data_save_log_peserta_campuran = array (
												'id_pemohon'=>$id_peserta,
												'id_pdln'=>$this->input->post('id_pdln'),
												'id_kategori_biaya'=>$jenis_pendanaan,
												'id_biaya'=>$insert_row_campuran,
												'start_date'=>strtotime($this->input->post('start_date_penugasan')),
												'end_date'=>strtotime($this->input->post('end_date_penugasan'))
										);
				$this->crud_ajax->init('t_log_peserta','id_log_peserta',NULL);
				$insert_row_log_peserta_campuran = $this->crud_ajax->save($data_save_log_peserta_campuran);
				//insert dana detail biaya campuran checkboxes
				$data_check_campuran = $this->input->post('check_jb[]');
				$id_jenis_biaya_campuran = array();
				$by_who = array();
				// 1_2 explode value checkbox which have id jenis biaya & by who pay?
				foreach ($data_check_campuran as $value) {
					$piece = explode("_",$value);
					array_push($id_jenis_biaya_campuran,$piece[0]);
					array_push($by_who,$piece[1]);
				}
				for($i = 0;$i < count($id_jenis_biaya_campuran);$i++){
					$data_det_biaya_campuran = array(
										'id_dana_campuran'=>(empty($insert_row_campuran)) ? NULL : $insert_row_campuran,
										'id_jenis_biaya'=>(empty($id_jenis_biaya_campuran[$i])) ? NULL : $id_jenis_biaya_campuran[$i],
										'by'=>(empty($by_who[$i])) ? NULL : $by_who[$i],
									);
					$this->crud_ajax->init('t_ref_pembiayaan_campuran','id_dana_campuran',NULL);
					$insert_row_det_campuran = $this->crud_ajax->save($data_det_biaya_campuran);
				}				
			}
		}
		echo json_encode($response);
	}
        public function update_peserta() {

            $response['status'] = TRUE;
            $response['id_pdln'] = $this->input->post('id_pdln');
            $response['id_log_peserta'] = $this->input->post('id_log_peserta');
            $id_log_peserta = $this->input->post('id_log_peserta');
            $peserta = $this->db->get_where('t_log_peserta', array('id_log_peserta' => $id_log_peserta));
            // Get data primary
            $id_pemohon = $peserta->row()->id_pemohon;
            $id_biaya = $peserta->row()->id_biaya;
            $id_kategori_biaya = $peserta->row()->id_kategori_biaya;

            // Delete pembiayaan
            if (!empty($id_kategori_biaya)) {
                if ($id_kategori_biaya == 1) {
                    $this->crud_ajax->init('t_pembiayaan_campuran', 'id_dana_campuran', NULL);
                    $this->crud_ajax->delete_by_id($id_biaya);
                } else if ($id_kategori_biaya == 0) {
                    $this->crud_ajax->init('t_ref_pembiayaan_tunggal', 'id_log_dana_tunggal', NULL);
                    $this->crud_ajax->delete_by_id($id_biaya);
                }
            }

            // Data Peserta atau Pemohon
            $id_peserta = $this->input->post('id_peserta');
            //Pembiayaan 0 Tunggal 1 Campuran
            $jenis_pendanaan = $this->input->post('opt_pendanaan');
            if ($jenis_pendanaan == '0') { // Tunggal
                $instansi_tunggal = $this->input->post('instansi_tunggal');
                $biaya_tunggal = $this->input->post('biaya_tunggal');

                $data_biaya_tunggal = array(
                    'id_instansi' => (empty($instansi_tunggal)) ? NULL : $instansi_tunggal,
                    'biaya' => (empty($biaya_tunggal)) ? NULL : convert_to_number($biaya_tunggal)
                );
                $this->crud_ajax->init('t_ref_pembiayaan_tunggal', 'id_log_dana_tunggal', NULL);
                $insert_row_tunggal = $this->crud_ajax->save($data_biaya_tunggal);
                if (empty($insert_row_tunggal)) { // If Failed then trans_rollback
                    $response['msg'] = "Simpan data pembiayaan gagal, harap coba lagi";
                    $response['status'] = FALSE;
                } else {

                  $this->crud_ajax->init('t_log_peserta', 'id_log_peserta', NULL);
                  $where = array(
                      'id_log_peserta' => $id_log_peserta
                  );
                  $data_save_log_peserta = array(
                        'id_pemohon' => $id_peserta,
                        'id_pdln' => $this->input->post('id_pdln'),
                        'id_kategori_biaya' => $jenis_pendanaan,
                        'id_biaya' => $insert_row_tunggal,
                        'start_date' => strtotime($this->input->post('start_date_penugasan')),
                        'end_date' => strtotime($this->input->post('end_date_penugasan'))
                    );
                    $this->crud_ajax->setExtraWhere($where);
                    $affected_rows = $this->crud_ajax->update($where, $data_save_log_peserta);
                    $insert_row_log_peserta_tunggal = $id_log_peserta;
                }
            } else if ($jenis_pendanaan == '1') { // Campuran
                $instansi_gov = $this->input->post('instansi_campuran_gov');
                $instansi_campuran_donor = $this->input->post('instansi_campuran_donor');
                $biaya_campuran = $this->input->post('biaya_campuran');

                $data_biaya_campuran = array(
                    'instansi_gov' => (empty($instansi_gov)) ? NULL : $instansi_gov,
                    'instansi_donor' => (empty($instansi_campuran_donor)) ? NULL : $instansi_campuran_donor,
                    'biaya_apbn' => (empty($biaya_campuran)) ? NULL : convert_to_number($biaya_campuran),
                );

                $this->crud_ajax->init('t_pembiayaan_campuran', 'id_dana_campuran', NULL);
                $insert_row_campuran = $this->crud_ajax->save($data_biaya_campuran);

                if (empty($insert_row_campuran)) { // If Failed then trans_rollback
                    $response['msg'] = "Simpan data pembiayaan campuran gagal, harap coba lagi";
                    $response['status'] = FALSE;
                    exit();
                } else {
                    //insert log peserta
                    $this->crud_ajax->init('t_log_peserta', 'id_log_peserta', NULL);
                    $where = array(
                        'id_log_peserta' => $id_log_peserta
                    );
                    $data_save_log_peserta = array(
                        'id_pemohon' => $id_peserta,
                        'id_pdln' => $this->input->post('id_pdln'),
                        'id_kategori_biaya' => $jenis_pendanaan,
                        'id_biaya' => $insert_row_campuran,
                        'start_date' => strtotime($this->input->post('start_date_penugasan')),
                        'end_date' => strtotime($this->input->post('end_date_penugasan'))
                    );
                    $this->crud_ajax->setExtraWhere($where);
                    $affected_rows = $this->crud_ajax->update($where, $data_save_log_peserta);
                    $insert_row_log_peserta_campuran = $id_log_peserta;

                    //insert dana detail biaya campuran checkboxes
                    $data_check_campuran = $this->input->post('check_jb[]');
                    $id_jenis_biaya_campuran = array();
                    $by_who = array();
                    // 1_2 explode value checkbox which have id jenis biaya & by who pay?
                    foreach ($data_check_campuran as $value) {
                        $piece = explode("_", $value);
                        array_push($id_jenis_biaya_campuran, $piece[0]);
                        array_push($by_who, $piece[1]);
                    }
                    for ($i = 0; $i < count($id_jenis_biaya_campuran); $i++) {
                        $data_det_biaya_campuran = array(
                            'id_dana_campuran' => (empty($insert_row_campuran)) ? NULL : $insert_row_campuran,
                            'id_jenis_biaya' => (empty($id_jenis_biaya_campuran[$i])) ? NULL : $id_jenis_biaya_campuran[$i],
                            'by' => (empty($by_who[$i])) ? NULL : $by_who[$i],
                        );
                        $this->crud_ajax->init('t_ref_pembiayaan_campuran', 'id_dana_campuran', NULL);
                        $insert_row_det_campuran = $this->crud_ajax->save($data_det_biaya_campuran);
                    }
                }
            }
            echo json_encode($response);
        }

	public function delete_peserta(){
		$response = array();
		$id = $this->input->post('id_log_peserta');
        $id_pdln = $this->input->post('id_pdln');

        $this->db->where('id_pdln',$id_pdln);
		// Berdasarkan tanggal first submit / create date insert row
		$create_date = $this->db->get('m_pdln')->row()->create_date;

        $this->crud_ajax->init('t_log_peserta','id_log_peserta',NULL);

        if(!$this->crud_ajax->delete_by_id($id))
            $response['status'] = FALSE;
        else{
            $init_file_peserta = $id_pdln.'_'.$id.'_';

            $this->db->like('dir_path',$init_file_peserta);
            $query = $this->db->get('m_dok_pdln');

            if($query->num_rows() > 0){            	
            	foreach ($query->result() as $row) {
            		$id_dok_pdln = $row->id_dok_pdln;
            		$file_name = $row->dir_path;
            		$this->crud_ajax->init('m_dok_pdln','id_dok_pdln',NULL);
            		$this->crud_ajax->delete_by_id($id_dok_pdln);// delete from database
            		delete_file_pdln("peserta",$id_pdln,date("Y-m-d",$create_date),$file_name);//delete file from directory upload pdlm
            	}
            	$response['status'] = TRUE;
            }else{
            	$response['status'] = TRUE;
            }
            
        }
        echo json_encode($response);
	}

	function get_id_status_approval($status) {
	    if ($status == "Focalpoint") {
	        return 2;
	    }else if ($status == "Analis") {
	        return 3;
	    } else if ($status == "Kasubag") {
	        return 4;
	    } else if ($status == "Kabag") {
	        return 5;
	    } else if ($status == "Kepala Biro") {
	        return 6;
	    } else if ($status == "Sesmen") {
	        return 7;
	    } else if ($status == "Mensesneg") {
	        return 8;
	    }
	}
}