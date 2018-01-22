<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Approval extends CI_Controller {
    function __construct() {
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
    public function index() {
        $this->task();
    }

    public function task() {
        $data['theme'] = 'pdln';
        $data['page'] = 'v_task';
        $data['title'] = 'Item Pekerjaan';
        $data['title_page'] = 'Item Pekerjaan';
        $data['breadcrumb'] = 'Item Pekerjaan';
        page_render($data);
    }

    public function task_list() {
        $id_user = $this->session->user_id;
        $data_user = $this->db->get_where('m_user', array('UserID' => $id_user))->row();
        $level = $data_user->level;
        $is_plh = $data_user->is_plh;

        if ($is_plh) {
            $jenis_plh = $this->db->select('jenis_plh')->where('id_user_plh', $id_user)
                            ->limit(1)->order_by('id_plh', 'desc')->get('t_log_plh')->row()->jenis_plh;
            $task_plh = $this->plh_task_level($jenis_plh);
        }
        $where = "";
        $handle_negara = $this->get_handle_negara($id_user);

        $this->crud_ajax->init('m_pdln', 'id_pdln', array('m_pdln.tgl_register' => 'asc'));
        if ($level == LEVEL_PEMOHON) {
            $where = array('m_pdln.status' => '1');
            // $this->db->where_in('negara', $handle_negara);
        }else if ($level == LEVEL_FOCALPOINT) {
            $where = array('m_pdln.status' => '2');
            $this->db->where_in('negara', $handle_negara);
        
        } else {
            $this->db->where_not_in('m_pdln.status', array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'));
        }

        $this->crud_ajax->setExtraWhere($where);

        $this->crud_ajax->set_select_field('m_pdln.id_pdln,m_pdln.no_register,m_pdln.tgl_surat_usulan_pemohon,m_pdln.status,m_pdln.no_surat_usulan_fp,m_pdln.create_date,m_kegiatan.NamaKegiatan,
                                            unit_kerja.Name as unit_pemohon,unit_kerja2.Name as unit_fp,m_pdln.jenis_permohonan');
        $join = array(
            'm_user as user1' => array('user1.UserID = m_pdln.unit_pemohon', 'left'),
            'm_unit_kerja_institusi as unit_kerja' => array('unit_kerja.ID = user1.unitkerja', 'left'),
            'm_kegiatan' => array('m_kegiatan.ID = m_pdln.id_kegiatan', 'left'),
            'm_user as user2' => array('user2.UserID = m_pdln.unit_fp', 'left'),
            'm_unit_kerja_institusi as unit_kerja2' => array('unit_kerja2.ID = user2.unitkerja', 'left')
        );

        $this->crud_ajax->setJoinField($join);
        $list = $this->crud_ajax->get_datatables();

        $data = array();
        if (isset($_POST['start'])) {
            $no = $_POST['start'];
        } else {
            $no = 0;
        }

        foreach ($list as $pdln) {
            $no++;
            $row = array();

            $row[] = $pdln->id_pdln;
            $row[] = str_pad($pdln->no_register, 8, '0', STR_PAD_LEFT);
            $row[] = date("d/m/Y", ($pdln->tgl_surat_usulan_pemohon));
            $row[] = $pdln->no_surat_usulan_fp;
            $row[] = $pdln->unit_pemohon;
            $row[] = $pdln->unit_fp;
            $row[] = '<span class="label label-' . setLabel($pdln->jenis_permohonan) . '">' . setJenisPermohonan($pdln->jenis_permohonan) . '</span>';
            $row[] = $pdln->NamaKegiatan;
            $row[] = '<span class="label label-danger">' . setStatus_doc($pdln->status) . '</span>';
            $row[] = '<a href="' . base_url() . 'kotak_surat/modify/edit_wizard/' . $pdln->id_pdln . '"><button class="btn btn-sm green btn-outline filter-submit margin-bottom"><i class="fa fa-search"></i> View</button></a>';
			// $row[] = '<a href="' . base_url() . 'kotak_surat/approval/edit_task/' . $pdln->id_pdln . '"><button class="btn btn-sm green btn-outline filter-submit margin-bottom"><i class="fa fa-search"></i> View</button></a>';
            $row[] = $pdln->tgl_surat_usulan_pemohon;
            $data[] = $row;
        }

        $output = array(
            "draw" => (isset($_POST['draw']) ? $_POST['draw'] : null),
            "recordsTotal" => $this->crud_ajax->count_all(),
            "recordsFiltered" => $this->crud_ajax->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }

    public function plh_task_level($jenis_plh) {
        $pdln_level = 0;
        if ($jenis_plh == 1) { //jika plh karo
            $pdln_level = 6;
        } else if ($jenis_plh == 2) { //jika plh sesmen
            $pdln_level = 7;
        } else if ($jenis_plh == 3) { //jika plh menteri
            $pdln_level = 8;
        }

        return $pdln_level;
    }

    public function edit_task($id_pdln) {

        $where = array('Status' => '1');

        /* $this->crud_ajax->init('r_level_pejabat','id',null);		
          $this->crud_ajax->setExtraWhere($where);
          $data['level_pejabat'] = $this->crud_ajax->get_data();

          $this->crud_ajax->init('r_jenis_kegiatan','ID',null);
          $this->crud_ajax->setExtraWhere($where);
          $data['jenis_kegiatan']		= $this->crud_ajax->get_data();

          $this->crud_ajax->init('r_negara','id',null);
          $this->crud_ajax->setExtraWhere($where);
          $data['negara']		= $this->crud_ajax->get_data();

          $this->crud_ajax->init('r_jenis_pembiayaan','ID',null);
          $this->crud_ajax->setExtraWhere($where);
          $data['jenis_pembiayaan'] = $this->crud_ajax->get_data();

          $this->crud_ajax->init('r_institution','ID',null);
          $this->crud_ajax->setExtraWhere($where);
          $data['list_instansi'] = $this->crud_ajax->get_data();
         */

        $this->crud_ajax->init('r_template_tembusan', 'ID', null);
        $this->crud_ajax->setExtraWhere($where);
        $data['list_temp_tembusan'] = $this->crud_ajax->get_data();

        $data['id_pdln'] = $id_pdln;

        $this->crud_ajax->init('t_approval_pdln', 'id', null);
        $where_pdln = array('id_pdln' => $id_pdln, 'is_done' => 1);
        $this->crud_ajax->setExtraWhere($where_pdln);
        $data['list_approval'] = $this->crud_ajax->get_data(); //get history approval 

        $this->db->select('p.id_pdln,p.id_kegiatan,p.no_surat_usulan_pemohon,p.tgl_surat_usulan_pemohon,p.no_surat_usulan_fp,
                            p.tgl_surat_usulan_fp,p.pejabat_sign_sp,p.id_level_pejabat,p.format_tembusan,p.jenis_permohonan,p.status,p.id_level_pejabat,p.author');
        $this->db->where('p.id_pdln', $id_pdln);
        $this->db->from('m_pdln p');
        // $this->db->join("r_level_pejabat lp", "lp.id = p.id_level_pejabat");

        $data['data_pdln'] = $this->db->get()->row();
        //-----------------------------------------------------------------------------------------------------
        // Memastikan bahwa level pejabat tertentu atau pengguna tertentu yang dapat melakukan perubahan data
        //-----------------------------------------------------------------------------------------------------
        $id_user = $this->session->user_id;
        $data_user = $this->db->get_where('m_user', array('UserID' => $id_user))->row();
        $level = $data_user->level;

        $this->config->load('pdln', TRUE);
        $data_integrity = $this->config->item('data_integrity', 'pdln');
        $id_level_pejabat = $data['data_pdln']->id_level_pejabat;
        if(!empty($data['data_pdln']) 
            && $data['data_pdln']->author != $id_user 
            && (array_key_exists($id_level_pejabat, $data_integrity) == false || $data_integrity[$id_level_pejabat] != $level)
            //&& in_array($data['data_pdln']->status, $pdln_status) == false 
            ){
            //show_error("Anda tidak memiliki akses terhadap halaman atau data di halaman ini. ", 403, "Forbidden");
        }
        //-----------------------------------------------------------------------------------------------------

        $this->db->select('m_kegiatan.ID,NamaKegiatan,StartDate,EndDate,r_negara.nmnegara,r_kota.nmkota,r_jenis_kegiatan.Nama as JenisKegiatan');
        $this->db->where('m_kegiatan.ID', $data['data_pdln']->id_kegiatan);
        $this->db->from('m_kegiatan');
        $this->db->join('r_negara', 'r_negara.id = m_kegiatan.negara');
        $this->db->join('r_kota', 'r_kota.id = m_kegiatan.tujuan');
        $this->db->join('r_jenis_kegiatan', 'r_jenis_kegiatan.ID = m_kegiatan.JenisKegiatan');

        $data['detail_kegiatan'] = $this->db->get()->row();
        $data['theme'] = 'pdln';
        $data['page'] = 'v_edit_task';
        $data['title'] = 'Form Persetujuan';
        $data['title_page'] = 'Form Persetujuan';
        $data['breadcrumb'] = 'Form Persetujuan';

        page_render($data);
    }

    public function get_file_path() {
        $id_pdln = $this->input->post('id_pdln');
        $this->db->where('id_pdln', $id_pdln);

        $row = $this->db->get('m_pdln')->row();

        $date_created = date("Y-m-d", $row->create_date);
        $file_pemohon = $row->path_file_sp_pemohon;
        $file_fp = $row->path_file_sp_fp;

        $response['status'] = TRUE;

        $path_pemohon = get_file_pdln("pdln", $date_created, $id_pdln, $file_pemohon);
        $path_fp = get_file_pdln("pdln", $date_created, $id_pdln, $file_fp);
        if (!empty($file_pemohon)) {
            $response['path_pemohon'] = $path_pemohon;
            $response['status_file_pemohon'] = TRUE;
            $response['msg'] = "Simpan data berhasil";
        } else
            $response['status_file_pemohon'] = FALSE;
        if (!empty($file_fp)) {
            $response['path_focal_point'] = $path_fp;
            $response['status_file_fp'] = TRUE;
        } else
            $response['status_file_fp'] = FALSE;
        echo json_encode($response);
    }

    public function get_file_kegiatan() {

        $id_jenis = $this->db->get_where('m_kegiatan', array('ID' => $this->input->post('id_jenis')))->row()->JenisKegiatan;
        $id_pdln = $this->input->post('id_pdln');
        $created_date = date("Y-m-d", ($this->db->get_where('m_pdln', array('id_pdln' => $id_pdln))->row()->create_date));

        $this->db->from('view_doc_kegiatan');
        $this->db->where('id_jenis_kegiatan', $id_jenis);
        $result = $this->db->get();
        $response = array();
        if ($result->num_rows() > 0) {
            $data['status'] = TRUE;
            foreach ($result->result() as $row) {
                $data = array();
                $data['id_jenis_doc'] = $row->id_jenis_doc;
                $data['nama_doc'] = $row->nama_doc;
                $data['nama_full_doc'] = ucwords($row->nama_full_doc);
                $data['is_require'] = ($row->is_require == '1' ? TRUE : FALSE );
                $data['id_jenis_kegiatan'] = $row->id_jenis_kegiatan;

                $id_jenis_doc = $row->id_jenis_doc;
                $where = array(
                    'id_jenis_doc' => $id_jenis_doc,
                    'id_pdln' => $id_pdln
                );
                $is_exist = $this->db->get_where('m_dok_pdln', $where);
                if ($is_exist->num_rows() > 0) {
                    $nama_file_doc = $is_exist->row()->dir_path;
                    $path_file = get_file_pdln("kegiatan", $created_date, $id_pdln, $nama_file_doc);
                    $data['path_file'] = $path_file;
                    $data['is_exist'] = TRUE;
                } else {
                    $data['is_exist'] = FALSE;
                }
                array_push($response, $data);
            }
        }
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function lanjutkan() {
        $this->_lanjutkan_validate();
        $response['status'] = TRUE;
        echo json_encode($response);
    }

    /**
     * @method private _validate handle validation data users 
     * @return json output status on form or modal 
     */
    private function _lanjutkan_validate() {
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['status'] = TRUE;

        $id_pdln = $this->input->post('id_pdln');
        $status = $this->input->post('status');
        $note = $this->input->post('note');
        $level = $this->input->post('level');
        $nextlevel = $this->input->post('nextlevel');
        $template_tembusan = $this->input->post('template_tembusan');

        $level_user = $this->db->get_where('m_user', array('UserID' => $this->session->user_id))->row()->level;

        if ($note === "") {
            $data['status'] = FALSE;
            $data['message'] = "Silahkan berikan catatan terlebih dahulu sebelum memberikan persetujuan";
            echo json_encode($data);
            exit;
        } else if ($template_tembusan === "0" || !isset($template_tembusan)) {
            if (($level_user == LEVEL_ANALIS) || ($level_user == LEVEL_KASUBAG) || ($level_user == LEVEL_KABAG)) {
                $data['status'] = FALSE;
                $data['message'] = "Silahkan pilih format tembusan terlebih dahulu";
                echo json_encode($data);
                exit;
            }
        }

        if ($data['status'] === TRUE) {
            /* Insert New Row To Next Approval */
            $this->crud_ajax->init('t_approval_pdln', 'id', null);
            $data_approval = array(
                'id_pdln' => $id_pdln,
                'assign_date' => date('Y-m-d H:i:s'),
                'level' => $nextlevel
            );

            $insert_id_u = $this->crud_ajax->save($data_approval);

            /* Update Current Data Approval */
            $data_update_approval = array(
                'user_id' => $this->session->user_id,
                'note' => $note,
                'submit_date' => date('Y-m-d H:i:s'),
                'aksi' => 'setuju',
                'is_done' => 1,
            );
            $where_approval = array('level' => $level, 'id_pdln' => $id_pdln, 'is_done' => 0);
            $affected_row_u = $this->crud_ajax->update($where_approval, $data_update_approval);

            /* Update status in pdln table */
            $this->crud_ajax->init('m_pdln', 'id', null);
            if (($level_user == LEVEL_ANALIS) || ($level_user == LEVEL_KASUBAG) || ($level_user == LEVEL_KABAG)) {
                $data_pdln = array(
                    'status' => intval($status) + 1,
                    'format_tembusan' => $template_tembusan
                );
            } else {
                $data_pdln = array(
                    'status' => intval($status) + 1,
                );
            }

            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);
        }
    }

    public function lanjutketu() {
        $response['id_pdln'] = $this->_lanjutketu_validate();
        $response['status'] = TRUE;
        echo json_encode($response);
    }

    /**
     * @method private _validate handle validation data users 
     * @return json output status on form or modal 
     */
    private function _lanjutketu_validate() {
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['status'] = TRUE;

        $id_pdln = $this->input->post('id_pdln');
        $status = $this->input->post('status');
        $level = $this->input->post('level');
        $nextlevel = $this->input->post('nextlevel');
        $note = $this->input->post('note');

        /* Insert New Row To Next Approval */
        $this->crud_ajax->init('t_approval_pdln', 'id', null);
        $data_approval = array(
            'id_pdln' => $id_pdln,
            'assign_date' => date('Y-m-d H:i:s'),
            'level' => $nextlevel
        );

        $insert_id_u = $this->crud_ajax->save($data_approval);

        /* Update Current Data Approval */
        $data_update_approval = array(
            'user_id' => $this->session->user_id,
            'submit_date' => date('Y-m-d H:i:s'),
            'note' => $note,
            'aksi' => 'setuju',
            'is_done' => 1,
        );
        $where_approval = array('level' => $level, 'id_pdln' => $id_pdln, 'is_done' => 0);
        $affected_row_u = $this->crud_ajax->update($where_approval, $data_update_approval);

        $level_user = $this->db->get_where('m_user', array('UserID' => $this->session->user_id))->row()->level;

        $curr_status = $this->db->get_where('m_pdln', array('id_pdln' => $id_pdln))->row()->status;

        if ($level_user == LEVEL_SESMEN && $curr_status == 7) {
            /* Update status in pdln table */
            $this->crud_ajax->init('m_pdln', 'id', null);
            $data_pdln = array(
                'status' => 9,
                'penandatangan_persetujuan' => $this->session->user_id
            );
            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);
        } else {
            /* Update status in pdln table */
            $this->crud_ajax->init('m_pdln', 'id', null);
            $data_pdln = array(
                'status' => 10,
                'penandatangan_persetujuan' => $this->session->user_id
            );
            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);
        }

        return $id_pdln;
    }

    //Kepala biro setuju
    public function setuju() {
        $this->_setuju_validate();
        $response['status'] = TRUE;
        echo json_encode($response);
    }

    /**
     * @method private _validate handle validation data users 
     * @return json output status on form or modal 
     */
    private function _setuju_validate() {
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['status'] = TRUE;

        $id_pdln = $this->input->post('id_pdln');
        $status = $this->input->post('status');
        $level = $this->input->post('level');
        $level_user = $this->db->get_where('m_user', array('UserID' => $this->session->user_id))->row()->level;
        $note = $this->input->post('note');

        if ($data['status'] === TRUE) {

            /* Update Current Data Approval */
            $data_update_approval = array(
                'user_id' => $this->session->user_id,
                'submit_date' => date('Y-m-d H:i:s'),
                'note' => $note,
                'aksi' => 'setuju',
                'is_done' => 1,
            );
            $this->crud_ajax->init('t_approval_pdln', 'id', null);
            $where_approval = array('level' => $level, 'id_pdln' => $id_pdln, 'is_done' => 0);
            $affected_row_u = $this->crud_ajax->update($where_approval, $data_update_approval);

            $this->db->select('m_pdln.id_pdln,m_pdln.id_kegiatan,m_pdln.no_sp,m_pdln.tgl_sp,m_pdln.tgl_surat_usulan_fp,m_pdln.no_surat_usulan_fp,m_pdln.pejabat_sign_sp,
                                m_pdln.format_tembusan, m_pdln.id_pdln_lama, m_pdln.jenis_permohonan');
            $this->db->where('m_pdln.id_pdln', $id_pdln);
            $data_pdln = $this->db->get('m_pdln')->row();
            
            // Get data for Update Perpanjang/Ralat/Pembatalan
            $id_pdln_lama = $data_pdln->id_pdln_lama;
            $jenis_permohonan = $data_pdln->jenis_permohonan;

            $jenis_kegiatan = $this->db->get_where('m_kegiatan', array('ID' => $data_pdln->id_kegiatan))->row()->JenisKegiatan;
            /* Update status in pdln table */
            $this->crud_ajax->init('m_pdln', 'id', null);

            $data_pdln = array(
                'no_sp' => $this->generate_number($jenis_kegiatan),
                'penandatangan_persetujuan' => $this->session->user_id,
                'tgl_sp' => strtotime(date('Y-m-d H:i:s')),
                'status' => 11,
                'barcode' => mt_rand(100000, 999999),
            );

            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);

            $sp_file = $this->cetak_permohonan_final($id_pdln);
            //update sp file
            $this->crud_ajax->init('m_pdln', 'id', null);
            $data_pdln = array(
                'path_sp' => $sp_file,
            );

            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);
            
            // Update Status SP LAMA menjadi diperpanjang/diralat/dibatalkan
            switch ($jenis_permohonan) {
                case "20":  // Perpanjangan
                    $status_baru = '14';    // Perpanjangan
                    $this->crud_ajax->init('m_pdln', 'id', null);
                    $data_pdln_lama = array(
                        'status' => $status_baru,
                    );
                    $where_pdln_lama = array('id_pdln' => $id_pdln_lama);
                    $affected_row_u = $this->crud_ajax->update($where_pdln_lama, $data_pdln_lama);
                    break;
                case "30":  // Ralat
                    $status_baru = '13';     // Ralat
                    $this->crud_ajax->init('m_pdln', 'id', null);
                    $data_pdln_lama = array(
                        'status' => $status_baru,
                    );
                    $where_pdln_lama = array('id_pdln' => $id_pdln_lama);
                    $affected_row_u = $this->crud_ajax->update($where_pdln_lama, $data_pdln_lama);
                    break;
                case "40":  // Pembatalan
                    $status_baru = '15';     // Pembatalan
                    $this->crud_ajax->init('m_pdln', 'id', null);
                    $data_pdln_lama = array(
                        'status' => $status_baru,
                    );
                    $where_pdln_lama = array('id_pdln' => $id_pdln_lama);
                    $affected_row_u = $this->crud_ajax->update($where_pdln_lama, $data_pdln_lama);
                    break;
            }
        }
    }

    public function tu_setuju() {
        $this->_tu_setuju_validate();
        $response['status'] = TRUE;
        echo json_encode($response);
    }

    /**
     * @method private _validate handle validation data users 
     * @return json output status on form or modal 
     */
    private function _tu_setuju_validate() {
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['status'] = TRUE;

        $id_pdln = $this->input->post('id_pdln');
        $no_sp = $this->input->post('no_sp');
        $tanggal_surat = $this->input->post('tanggal_surat');
        $level = $this->input->post('level');

        $level_user = $this->db->get_where('m_user', array('UserID' => $this->session->user_id))->row()->level;

        if ($no_sp === "") {
            $data['status'] = FALSE;
            $data['message'] = "Nomor Surat Harus Di isi";
            echo json_encode($data);
            exit;
        }

        if ($tanggal_surat === "") {
            $data['status'] = FALSE;
            $data['message'] = "Tanggal Surat Harus Di isi";
            echo json_encode($data);
            exit;
        }

        if ($data['status'] === TRUE) {
            //generate final sp 

            $sp_file = $this->cetak_permohonan_final($id_pdln);

            /* Update Current Data Approval */
            $data_update_approval = array(
                'user_id' => $this->session->user_id,
                'submit_date' => date('Y-m-d H:i:s'),
                'aksi' => 'setuju',
                'is_done' => 1,
            );
            $this->crud_ajax->init('t_approval_pdln', 'id', null);
            $where_approval = array('level' => $level, 'id_pdln' => $id_pdln, 'is_done' => 0);
            $affected_row_u = $this->crud_ajax->update($where_approval, $data_update_approval);

            /* Update status in pdln table */
            $this->crud_ajax->init('m_pdln', 'id', null);
            $data_pdln = array(
                'no_sp' => $no_sp,
                'tgl_sp' => strtotime($tanggal_surat),
                'status' => 11,
                'path_sp' => $sp_file
            );

            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);
        }
    }

    public function tolak() {
        $this->_tolak_validate();
        $response['status'] = TRUE;
        echo json_encode($response);
    }

    /**
     * @method private _validate handle validation data users 
     * @return json output status on form or modal 
     */
    private function _tolak_validate() {
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['status'] = TRUE;

        $id_pdln = $this->input->post('id_pdln');
        // $status = $this->input->post('status');
        // $level = $this->input->post('level');
        // $level = $this->session->level;
        $note = $this->input->post('note');

        $response['note'] = $note;
        $level_user = $this->db->get_where('m_user', array('UserID' => $this->session->user_id))->row()->level;

        if ($note === "") {
            $data['status'] = FALSE;
            $data['message'] = "Silahkan berikan catatan terlebih dahulu sebelum memberikan penolakan";
            echo json_encode($data);
            exit;
        }

        if ($data['status'] === TRUE) {
            /* Insert New Row To Next Approval */
            $this->crud_ajax->init('t_approval_pdln', 'id', null);
            $data_approval = array(
                'id_pdln' => $id_pdln,
                'assign_date' => date('Y-m-d H:i:s'),
                'level' => 'Focalpoint'
            );

            $insert_id_u = $this->crud_ajax->save($data_approval);

            /* Update Current Data Approval */
            $data_update_approval = array(
                'user_id' => $this->session->user_id,
                'note' => $note,
                'submit_date' => date('Y-m-d H:i:s'),
                'aksi' => 'tolak',
                'is_done' => 1
            );

            $where_approval = array('level' => $level_user, 'id_pdln' => $id_pdln, 'is_done' => 0);
            $affected_row_u = $this->crud_ajax->update($where_approval, $data_update_approval);

            /* Update status in pdln table */
            $this->crud_ajax->init('m_pdln', 'id', null);

            $data_pdln = array(
                'status' => 0,
            );

            $where_pdln = array('id_pdln' => $id_pdln);
            $affected_row_u = $this->crud_ajax->update($where_pdln, $data_pdln);
        }
    }

    public function generate_number($JenisKegiatan) {
        $penomoran = $this->db->get_where('t_suratkeluar_increment', array('Status' => 1))->row();
        $jns_kegiatan = $this->db->get_where('r_jenis_kegiatan', array('ID' => $JenisKegiatan))->row();
        $number = intval($penomoran->Nomor) + 1;

        $number_str = (string) $number;
        // for format length nomor surat is 8 digit,make the zero number show in fix_number var
        for ($x = strlen($number_str); $x < 8; $x++) {
            $number_str = '0' . $number_str;
        }
        $fix_number = $penomoran->InitialCode . "-" . $number_str . "/" . $penomoran->Formatting . "/" . $jns_kegiatan->Kodifikasi . "/" . date('m') . "/" . date('Y');

        $data = array('Nomor' => $number);
        $this->db->where('Status', 1);
        $this->db->update('t_suratkeluar_increment', $data);

        return $fix_number;
    }

    public function get_parent_id() {
        $this->db->where('UserID', $this->session->userdata('user_id'));
        $parent = $this->db->get('m_user')->row()->unitkerja;
        return $parent; //unitkerja user fp sbg parent 
    }

    public function get_list_pemohon() {
        $this->db->where('m_unit.Parent', $this->get_parent_id());
        $this->db->where('m_unit.Status', '1');
        $this->db->where_in('m_unit.Group', array(1, 2));
        $this->db->from('m_unit_kerja_institusi as m_unit');
        $this->db->join('m_user as mu', 'm_unit.ID = mu.unitkerja', 'left');

        $query = $this->db->get();
        if ($query->num_rows() > 0)
            return $query->result();
        return FALSE;
    }

    public function get_kegiatan() {
        $id_jenis = $this->input->post('id_jenis');
        $where = array(
            'JenisKegiatan' => $id_jenis,
            'Status' => '1'
        );
        $this->crud_ajax->init('m_kegiatan', 'ID', null);
        $this->crud_ajax->setExtraWhere($where);
        $query = $this->crud_ajax->get_data();
        if (count($query) > 0) {
            foreach ($query as $row) {
                echo '<option value="">--Pilih--</option>';
                echo '<option value="' . $row->ID . '">' . trim($row->NamaKegiatan) . '</option>';
            }
        } else
            echo '<option value="">--Kegiatan Tidak Tersedia--</option>';
    }

    public function get_detail_keg() {
        $id_kegiatan = $this->input->post('id_keg');
        $data = array();
        $this->db->from('view_kegiatan');
        $this->db->where('id_kegiatan', $id_kegiatan);
        $result = $this->db->get();
        if ($result->num_rows() > 0) {
            $data['status'] = TRUE;
            foreach ($result->result() as $row) {
                $data['penyelenggara'] = $row->penyelenggara;
                $data['negara'] = $row->nmnegara;
                $data['kota'] = $row->nmkota;
                $data['tgl_mulai_kegiatan'] = day($row->tgl_mulai_kegiatan);
                $data['tgl_akhir_kegiatan'] = day($row->tgl_akhir_kegiatan);
            }
            $data['status'] = TRUE;
        } else
            $data['status'] = FALSE;
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    public function progress() {

        $data['theme'] = 'pdln';
        $data['page'] = 'v_progress';
        $data['title'] = 'Permohonan Dalam Proses';
        $data['title_page'] = 'Permohonan Dalam Proses';
        $data['breadcrumb'] = 'Permohonan Dalam Proses';
        page_render($data);
    }

    public function progress_list() {
        $id_user = $this->session->user_id;
        $level = $this->db->get_where('m_user', array('UserID' => $id_user))->row()->level;
        $where = "";
        $handle_negara = $this->get_handle_negara($id_user);

        $this->crud_ajax->init('m_pdln', 'id_pdln', array('m_pdln.tgl_surat_usulan_pemohon' => 'asc'));

         if ($level == LEVEL_PEMOHON) {
            $this->db->where('m_pdln.status =', "0");
            $this->db->where('m_pdln.unit_pemohon =', $id_user);
        }else if ($level == LEVEL_FOCALPOINT) {
            $this->db->where('m_pdln.status =', "12");
            // $this->db->where('m_pdln.unit_pemohon =', $id_user);

            $this->db->where_in('negara', $handle_negara);
        }

        $this->crud_ajax->set_select_field('m_pdln.id_pdln,m_pdln.no_register,m_pdln.tgl_surat_usulan_pemohon,m_pdln.status,m_pdln.no_surat_usulan_fp,m_pdln.create_date,m_kegiatan.NamaKegiatan,
                                            unit_kerja.Name as unit_pemohon,unit_kerja2.Name as unit_fp,m_pdln.jenis_permohonan');
        
         if ($level == LEVEL_PEMOHON) {

        $join = array(
            'm_user as user1' => array('user1.UserID = m_pdln.unit_pemohon', 'left'),
            'm_unit_kerja_institusi as unit_kerja' => array('unit_kerja.ID = user1.unitkerja', 'left'),
            'm_kegiatan' => array('m_kegiatan.ID = m_pdln.id_kegiatan', 'left'),
            // 'm_user as user2' => array('user2.UserID = m_pdln.unit_fp', 'left'),
            'm_unit_kerja_institusi as unit_kerja2' => array('unit_kerja2.ID = user1.unitkerja', 'left')
        );
        }else if  ($level == LEVEL_FOCALPOINT) {
            $join = array(
            'm_user as user2' => array('user2.UserID = m_pdln.unit_fp', 'left'),
            'm_unit_kerja_institusi as unit_kerja' => array('unit_kerja.ID = user2.unitkerja', 'left'),
            'm_kegiatan' => array('m_kegiatan.ID = m_pdln.id_kegiatan', 'left'),
            'm_unit_kerja_institusi as unit_kerja2' => array('unit_kerja2.ID = user2.unitkerja', 'left')
            );
        }

        $this->crud_ajax->setJoinField($join);
        $list = $this->crud_ajax->get_datatables();

        $data = array();
        if (isset($_POST['start'])) {
            $no = $_POST['start'];
        } else {
            $no = 0;
        }

        foreach ($list as $pdln) {
            $no++;
            $row = array();

            $row[] = $pdln->id_pdln;
            $row[] = str_pad($pdln->no_register, 8, '0', STR_PAD_LEFT);
            $row[] = date("d/m/Y", ($pdln->tgl_surat_usulan_pemohon));
            $row[] = $pdln->no_surat_usulan_fp;
            $row[] = $pdln->unit_pemohon;
            $row[] = $pdln->unit_fp;
            $row[] = '<span class="label label-' . setLabel($pdln->jenis_permohonan) . '">' . setJenisPermohonan($pdln->jenis_permohonan) . '</span>';
            $row[] = $pdln->NamaKegiatan;
            $row[] = '<span class="label label-danger">' . setStatus_doc($pdln->status) . '</span>';
             // if ($level == LEVEL_PEMOHON) {
                // $row[] = '<a href="' . base_url() . 'kotak_surat/approval/edit_task/' . $pdln->id_pdln . '"><button class="btn btn-sm green btn-outline filter-submit margin-bottom"><i class="fa fa-search"></i> Viewxxx</button></a>';
             // }else if  ($level == LEVEL_FOCALPOINT) {
            $row[] = '<a href="' . base_url() . 'kotak_surat/modify/edit_wizard/' . $pdln->id_pdln . '"><button class="btn btn-sm green btn-outline filter-submit margin-bottom"><i class="fa fa-search"></i> View</button></a>';
            // }
			$row[] = $pdln->tgl_surat_usulan_pemohon;
            $data[] = $row;
        }
        $output = array(
            "draw" => (isset($_POST['draw']) ? $_POST['draw'] : null),
            "recordsTotal" => $this->crud_ajax->count_all(),
            "recordsFiltered" => $this->crud_ajax->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }

    public function done() {

        $data['theme'] = 'pdln';
        $data['page'] = 'v_done';
        $data['title'] = 'Permohonan Sudah Disetujui';
        $data['title_page'] = 'Permohonan Sudah Disetujui';
        $data['breadcrumb'] = 'Permohonan Sudah Disetujui';
        page_render($data);
    }

    public function done_list() {
        $id_user = $this->session->user_id;
        $level = $this->db->get_where('m_user', array('UserID' => $id_user))->row()->level;
        $where = "";
        $handle_negara = $this->get_handle_negara($id_user);

        $this->crud_ajax->init('m_pdln', 'id_pdln', array('m_pdln.tgl_register' => 'asc'));

        if ($level == LEVEL_ANALIS || $level == LEVEL_KASUBAG) {
            $this->db->where_in('negara', $handle_negara);
            $where = array('m_pdln.status' => '11');
        } else if ($level == LEVEL_KABAG || $level == LEVEL_KARO || $level == LEVEL_SESMEN || $level == LEVEL_TUSESMEN || $level == LEVEL_MENSESNEG || $level == LEVEL_TUMENSESNEG) {
            $where = array('m_pdln.status' => '11');
        } else {
            $where = array('m_pdln.status' => 'not defined');
        }

        $this->crud_ajax->setExtraWhere($where);

        $this->crud_ajax->set_select_field('m_pdln.id_pdln,m_pdln.no_register,m_pdln.tgl_register,m_pdln.status,m_pdln.no_surat_usulan_fp,m_pdln.create_date,m_kegiatan.NamaKegiatan,
                                            unit_kerja.Name as unit_pemohon,unit_kerja2.Name as unit_fp,m_pdln.jenis_permohonan');
        $join = array(
            'm_user as user1' => array('user1.UserID = m_pdln.unit_pemohon', 'left'),
            'm_unit_kerja_institusi as unit_kerja' => array('unit_kerja.ID = user1.unitkerja', 'left'),
            'm_kegiatan' => array('m_kegiatan.ID = m_pdln.id_kegiatan', 'left'),
            'm_user as user2' => array('user2.UserID = m_pdln.unit_fp', 'left'),
            'm_unit_kerja_institusi as unit_kerja2' => array('unit_kerja2.ID = user2.unitkerja', 'left')
        );

        $this->crud_ajax->setJoinField($join);
        $list = $this->crud_ajax->get_datatables();

        $data = array();
        if (isset($_POST['start'])) {
            $no = $_POST['start'];
        } else {
            $no = 0;
        }

        foreach ($list as $pdln) {
            $no++;
            $row = array();

            $row[] = $pdln->id_pdln;
            $row[] = str_pad($pdln->no_register, 8, '0', STR_PAD_LEFT);
            $row[] = date("d/m/Y", ($pdln->tgl_register));
            $row[] = $pdln->no_surat_usulan_fp;
            $row[] = $pdln->unit_pemohon;
            $row[] = $pdln->unit_fp;
            $row[] = '<span class="label label-' . setLabel($pdln->jenis_permohonan) . '">' . setJenisPermohonan($pdln->jenis_permohonan) . '</span>';
            $row[] = $pdln->NamaKegiatan;
            $row[] = '<span class="label label-info">' . setStatus_doc($pdln->status) . '</span>';
            $row[] = '<a href="' . base_url() . 'kotak_surat/approval/edit_task/' . $pdln->id_pdln . '"><button class="btn btn-sm green btn-outline filter-submit margin-bottom"><i class="fa fa-search"></i> View</button></a>';
            $row[] = '<button type="button" id="download_sp" title="Unduh" class="btn btn-xs btn-info"><i class="fa fa-download"></i> </button>';
            $row[] = $pdln->tgl_register;
			$data[] = $row;
        }
        $output = array(
            "draw" => (isset($_POST['draw']) ? $_POST['draw'] : null),
            "recordsTotal" => $this->crud_ajax->count_all(),
            "recordsFiltered" => $this->crud_ajax->count_filtered(),
            "data" => $data
        );
        echo json_encode($output);
    }

    public function get_handle_negara($id_user) {
        $this->db->select('RoleID');
        $this->db->from('t_user_role');
        $this->db->where('UserID', $id_user);

        $query = $this->db->get();
        $list_role = $query->result();

        $list_negara = array();
        foreach ($list_role as $role) {
            $id = $this->input->post('RoleID');
            $this->db->select('*');
            $this->db->from('t_role_negara');
            $this->db->where('RoleID', $role->RoleID);
            $query = $this->db->get();

            foreach ($query->result() as $row) {
                $list_negara[] = $row->NegaraID;
            }
        }

        return $list_negara;
    }

    public function get_data_pdln() {
        $response = array();
        $id_pdln = $this->input->post('id_pdln');
        $this->db->select('p.id_kegiatan,p.no_surat_usulan_pemohon,p.tgl_surat_usulan_pemohon,p.id_level_pejabat,p.no_surat_usulan_fp,p.pejabat_sign_sp');
        $this->db->from('m_pdln as p');
        $this->db->where('id_pdln', $id_pdln);

        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $response = array(
                'id_kegiatan' => $row->id_kegiatan,
                'no_surat_usulan_pemohon' => $row->no_surat_usulan_pemohon,
                'tgl_surat_usulan_pemohon' => $row->tgl_surat_usulan_pemohon,
                'id_level_pejabat' => $row->id_level_pejabat,
                'no_surat_usulan_fp' => $row->no_surat_usulan_fp,
                'pejabat_sign_sp' => $row->pejabat_sign_sp,
                'status' => TRUE
            );
        } else {
            $response['status'] = FALSE;
        }
        echo json_encode($response);
    }

    public function cetak_permohonan_final($id_surat) {
        setlocale(LC_ALL, 'id_ID');

        $this->db->select('m_pdln.id_pdln,m_pdln.id_kegiatan,m_pdln.id_level_pejabat,m_pdln.no_sp,m_pdln.tgl_sp,m_pdln.tgl_surat_usulan_fp,m_pdln.no_surat_usulan_fp,m_pdln.pejabat_sign_sp,
                            m_pdln.format_tembusan,m_pdln.penandatangan_persetujuan,m_pdln.barcode,r_kota.nmkota,m_pdln.update_date,m_pdln.create_date');
        $this->db->join('m_user', 'm_user.UserID = m_pdln.unit_fp');
        $this->db->join('r_institution', 'r_institution.ID = m_user.instansi');
        $this->db->join('r_kota', 'r_kota.id = r_institution.Kota');

        $this->db->where('m_pdln.id_pdln', $id_surat);
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
        $this->db->join('r_unit_tembusan', 'r_unit_tembusan.ID = t_template_unit_tembusan.UnitID');
        $this->db->where('TemplateID', $result_data->format_tembusan);
        $unit_tembusan = $this->db->get('t_template_unit_tembusan')->result();

        $this->db->select('t_log_peserta.id_log_peserta,t_log_peserta.start_date,t_log_peserta.end_date,m_pemohon.nama,m_pemohon.jabatan,m_pemohon.nip_nrp,
                            r_institution.Nama as instansi,m_pemohon.instansi_lainnya,m_pemohon.id_instansi,t_log_peserta.id_kategori_biaya,t_log_peserta.id_biaya');
        $this->db->join('m_pemohon', 'm_pemohon.id_pemohon = t_log_peserta.id_pemohon');
        $this->db->join('r_institution', 'r_institution.ID = m_pemohon.id_instansi','left');
        $this->db->where('id_pdln', $id_surat);
        $temp_pemohon = $this->db->get('t_log_peserta');

        $list_pemohon = array();
        foreach ($temp_pemohon->result() as $pemohon) {
            $list_pemohon[$pemohon->id_log_peserta] = $pemohon;
            $list_pemohon[$pemohon->id_log_peserta]->pembiayaan = $this->_get_detail_pembiayaan($pemohon->id_kategori_biaya, $pemohon->id_biaya); // Get the categories sub categories
        }

        $this->db->select('m_kegiatan.NamaKegiatan, m_kegiatan.StartDate, m_kegiatan.EndDate, r_negara.nmnegara as Negara');
        $this->db->join('r_negara', 'r_negara.ID = m_kegiatan.negara', 'left');
        $this->db->where('m_kegiatan.ID', $result_data->id_kegiatan);
        $kegiatan = $this->db->get('m_kegiatan')->row();

        $this->db->select('*');
        $this->db->where('m_user.UserID', $result_data->penandatangan_persetujuan);
        $penandatangan_persetujuan = $this->db->get('m_user')->row();

        $label_penandatangan = $this->get_label_penandatangan($result_data->penandatangan_persetujuan, $result_data->id_level_pejabat);

        $data = array(
            'title' => "Surat Persetujuan",
            'unit_tembusan' => $unit_tembusan,
            'data_sp' => $result_data,
            'kegiatan' => $kegiatan,
            'penandatangan' => $penandatangan_persetujuan,
            'label_penandatangan' => $label_penandatangan,
            'm_pdf' => $this->load->library('M_pdf'));
        $html = $this->load->view('kotak_surat/v_print_permohonan', $data, TRUE);

        $data = array(
            'title' => "Lampiran SP",
            'data_lampiran' => $result_data,
            'kegiatan' => $kegiatan,
            'list_pemohon' => $list_pemohon,
            'label_penandatangan' => $label_penandatangan,
            'm_pdf' => $this->load->library('M_pdf'));
        $html_lampiran = $this->load->view('kotak_surat/v_print_lampiran_permohonan', $data, TRUE);


        $this->load->library('M_pdf');
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        if (isset($result_data->barcode)) {
            $this->m_pdf->pdf->SetHTMLFooter('<barcode code="' . $result_data->barcode . '" type="EAN13" size="0.5" height="1.0" alt= "testing"/>');
        }
        $this->m_pdf->pdf->WriteHTML($html);

        $this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer   

        if (isset($result_data->barcode)) {
            $this->m_pdf->pdf->SetHTMLFooter('<barcode code="' . $result_data->barcode . '" type="EAN13" size="0.5" height="1.0" alt= "testing"/>');
        }

        $this->m_pdf->pdf->WriteHTML($html_lampiran);

        setlocale(LC_ALL, 'id_ID');
        
        $this->m_pdf->debug = true;
        $this->m_pdf->pdf->Output($fullpath, 'F');
        send_file_to_browser($fullpath);
        //$this->m_pdf->pdf->Output($filename,'I');         

        return $filename;
    }

    private function _get_detail_pembiayaan($id_karegori_biaya, $id_biaya) {
        if ($id_karegori_biaya == "0") {
            $this->db->select('t_ref_pembiayaan_tunggal.id_log_dana_tunggal,r_institution.Nama');
            $this->db->where('id_log_dana_tunggal', $id_biaya);
            $this->db->join('r_institution', "r_institution.ID = t_ref_pembiayaan_tunggal.id_instansi", "left");
            return $this->db->get('t_ref_pembiayaan_tunggal')->row()->Nama;
        } else if ($id_karegori_biaya == "1") {

            $this->db->select('t_pemb.id_dana_campuran,ref_camp.by,r_jenis_pembiayaan.Description AS jenis_biaya, 
                                (CASE WHEN ref_camp.by=1 THEN t_pemb.instansi_gov WHEN ref_camp.by=2 THEN t_pemb.instansi_donor 
                                ELSE 0 END) AS id_instansi_pembiayaan', false);
            $this->db->where('t_pemb.id_dana_campuran', $id_biaya);
            $this->db->from('t_pembiayaan_campuran t_pemb');
            $this->db->join('t_ref_pembiayaan_campuran as ref_camp', "t_pemb.id_dana_campuran = ref_camp.id_dana_campuran");
            $this->db->join('r_jenis_pembiayaan', "r_jenis_pembiayaan.ID = ref_camp.id_jenis_biaya");

            $pembiayaan = "";
            foreach ($this->db->get()->result() as $pembiaya) {
                $id_instansi = $pembiaya->id_instansi_pembiayaan;
                if ($id_instansi == 0) {
                    $pembiayaan = $pembiayaan . "- " . $pembiaya->jenis_biaya . " : Perseorangan";
                } else {
                    $pembiayaan = $pembiayaan . "- " . $pembiaya->jenis_biaya . " : " . $this->db->select('*')->where("ID = $id_instansi")->get('r_institution')->row()->Nama . '<br/><br/>';
                }
            }

            return $pembiayaan;
        }
    }

    public function print_permohonan($id_surat) {
        setlocale(LC_ALL, 'id_ID');

        $this->db->select('m_pdln.id_pdln,m_pdln.id_kegiatan,m_pdln.id_level_pejabat,m_pdln.no_sp,m_pdln.tgl_sp,m_pdln.tgl_surat_usulan_pemohon,m_pdln.no_surat_usulan_pemohon,m_pdln.pejabat_sign_sp,
                            m_pdln.path_sp,m_pdln.format_tembusan,m_pdln.penandatangan_persetujuan,m_pdln.barcode,r_kota.nmkota,m_pdln.update_date,m_pdln.create_date');
        $this->db->join('m_kegiatan', 'm_kegiatan.ID = m_pdln.id_kegiatan');
        $this->db->join('r_kota', 'r_kota.id = m_kegiatan.Tujuan');

        // $this->db->join('r_kota', 'r_kota.id = m_kegiatan.Kota');

        $this->db->where('m_pdln.id_pdln', $id_surat);
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
        $this->db->join('r_unit_tembusan', 'r_unit_tembusan.ID = t_template_unit_tembusan.UnitID');
        $this->db->where('TemplateID', $result_data->format_tembusan);
        $unit_tembusan = $this->db->get('t_template_unit_tembusan')->result();

        $this->db->select('t_log_peserta.id_log_peserta,t_log_peserta.start_date,t_log_peserta.end_date,m_pemohon.nama,m_pemohon.jabatan,m_pemohon.nip_nrp,
                            r_institution.Nama as instansi,m_pemohon.instansi_lainnya,m_pemohon.id_instansi,t_log_peserta.id_kategori_biaya,t_log_peserta.id_biaya');
        $this->db->join('m_pemohon', 'm_pemohon.id_pemohon = t_log_peserta.id_pemohon');
        $this->db->join('r_institution', 'r_institution.ID = m_pemohon.id_instansi');
        $this->db->where('id_pdln', $id_surat);
        $temp_pemohon = $this->db->get('t_log_peserta');

        $list_pemohon = array();
        foreach ($temp_pemohon->result() as $pemohon) {
            $list_pemohon[$pemohon->id_log_peserta] = $pemohon;
            $list_pemohon[$pemohon->id_log_peserta]->pembiayaan = $this->_get_detail_pembiayaan($pemohon->id_kategori_biaya, $pemohon->id_biaya); // Get the categories sub categories
        }

        $this->db->select('m_kegiatan.NamaKegiatan,m_kegiatan.StartDate,m_kegiatan.EndDate,r_negara.nmnegara as Negara');
        $this->db->join('r_negara', 'r_negara.ID = m_kegiatan.negara', 'left');
        $this->db->where('m_kegiatan.ID', $result_data->id_kegiatan);
        $kegiatan = $this->db->get('m_kegiatan')->row();

        $this->db->select('*');
        $this->db->where('m_user.UserID', $result_data->penandatangan_persetujuan);
        $penandatangan_persetujuan = $this->db->get('m_user')->row();

        $label_penandatangan = $this->get_label_penandatangan($result_data->penandatangan_persetujuan, $result_data->id_level_pejabat);

        $data = array(
            'title' => "Surat Persetujuan",
            'unit_tembusan' => $unit_tembusan,
            'data_sp' => $result_data,
            'kegiatan' => $kegiatan,
            'penandatangan' => $penandatangan_persetujuan,
            'label_penandatangan' => $label_penandatangan,
            'm_pdf' => $this->load->library('M_pdf'));
        $html = $this->load->view('kotak_surat/v_print_permohonan', $data, TRUE);

        $data = array(
            'title' => "Lampiran SP",
            'data_lampiran' => $result_data,
            'kegiatan' => $kegiatan,
            'list_pemohon' => $list_pemohon,
            'label_penandatangan' => $label_penandatangan,
            'm_pdf' => $this->load->library('M_pdf'));
        $html_lampiran = $this->load->view('kotak_surat/v_print_lampiran_permohonan', $data, TRUE);

        //$filename = 'sp_pdln' . $id_surat . '_' . date('d_m_Y');

        $this->load->library('M_pdf');
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        if (isset($result_data->barcode)) {
            $this->m_pdf->pdf->SetHTMLFooter('<barcode code="' . $result_data->barcode . '" type="EAN13" size="0.5" height="1.0" alt= "testing"/>');
        }
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $this->m_pdf->pdf->WriteHTML($html);

        $this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait 
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer   

        if (isset($result_data->barcode)) {
            $this->m_pdf->pdf->SetHTMLFooter('<barcode code="' . $result_data->barcode . '" type="EAN13" size="0.5" height="1.0" alt= "testing"/>');
        }
        $html_lampiran = mb_convert_encoding($html_lampiran, 'UTF-8', 'UTF-8');
        $this->m_pdf->pdf->WriteHTML($html_lampiran);

        //$this->m_pdf->pdf->Output($filename . '.pdf', 'I');
        $this->m_pdf->pdf->Output($fullpath, 'F');
        
        // update sp_path on table m_pdln
        $this->db->reset_query();
        $this->db->set('path_sp', $filename);
        $this->db->where('id_pdln', $id_surat);
        if (! $this->db->update('m_pdln'))
        {
            // if false
            die('Gagal mengupdate data path_sp, silahkan hubungi Administrator.');
        }
        
        send_file_to_browser($fullpath);
    }

    public function print_perpanjangan($id_surat) {
        setlocale(LC_ALL, 'id_ID');

        $this->db->select('m_pdln.id_pdln, m_pdln.id_kegiatan, m_pdln.no_sp, m_pdln.tgl_sp, m_pdln.tgl_surat_usulan_fp, 
                            m_pdln.no_surat_usulan_fp, m_pdln.pejabat_sign_sp, m_pdln.format_tembusan, m_pdln.id_pdln_lama,m_pdln.create_date,m_pdln.update_date');

        $this->db->where('m_pdln.id_pdln', $id_surat);
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

        $filename = "resi_sm_{$id_surat}_{$update_date}.pdf";
        $fullpath = "{$targetPath}{$filename}";

        if (file_exists($fullpath)){
            send_file_to_browser($fullpath); // this function will exec die() and exit
        }
        // ----------------------------------------------------------------------

        $this->db->select('r_unit_tembusan.Nama');
        $this->db->join('r_unit_tembusan', 'r_unit_tembusan.ID = t_template_unit_tembusan.UnitID');
        $this->db->where('TemplateID', $result_data->format_tembusan);
        $unit_tembusan = $this->db->get('t_template_unit_tembusan')->result();

        $this->db->select('t_log_peserta.id_log_peserta,m_pemohon.nama,m_pemohon.jabatan');
        $this->db->join('m_pemohon', 'm_pemohon.id_pemohon = t_log_peserta.id_pemohon');
        $this->db->where('id_pdln', $id_surat);
        $list_pemohon = $this->db->get('t_log_peserta')->result();

        $this->db->select('m_kegiatan.NamaKegiatan');
        $this->db->where('m_kegiatan.ID', $result_data->id_kegiatan);
        $kegiatan = $this->db->get('m_kegiatan')->row();
        
        $this->db->select('m_pdln.no_sp, m_pdln.tgl_sp');
        $this->db->where('m_pdln.id_pdln', $result_data->id_pdln_lama);
        $pdln_lama = $this->db->get('m_pdln')->row();

        $data = array(
            'title' => "Surat Persetujuan",
            'unit_tembusan' => $unit_tembusan,
            'data_sp' => $result_data,
            'kegiatan' => $kegiatan,
            'data_sp_lama' => $pdln_lama,
            'm_pdf' => $this->load->library('M_pdf'));
        $html = $this->load->view('kotak_surat/v_print_perpanjangan', $data, TRUE);

        $data = array(
            'title' => "Lampiran SP",
            'data_lampiran' => $result_data,
            'list_pemohon' => $list_pemohon,
            'm_pdf' => $this->load->library('M_pdf'));
        $html_lampiran = $this->load->view('kotak_surat/v_print_lampiran_perpanjangan', $data, TRUE);

        $this->load->library('M_pdf');
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html);

        $this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html_lampiran);

        //$filename = 'resi_sm_' . $id_surat . '_' . date('d_m_Y');
        $this->m_pdf->pdf->Output($fullpath, 'F');
        send_file_to_browser($fullpath); // this function will exec die() and exit
    }

    public function print_ralat($id_surat) {
        setlocale(LC_ALL, 'id_ID');

        $this->db->select('m_pdln.id_pdln, m_pdln.id_kegiatan, m_pdln.no_sp, m_pdln.tgl_sp, m_pdln.tgl_surat_usulan_fp,
                            m_pdln.no_surat_usulan_fp, m_pdln.pejabat_sign_sp, m_pdln.format_tembusan, m_pdln.id_pdln_lama,m_pdln.update_date,m_pdln.create_date');

        $this->db->where('m_pdln.id_pdln', $id_surat);
        $result_data = $this->db->get('m_pdln')->row();

        // ----------------------------------------------------------------------
        // checking existing file
        // ----------------------------------------------------------------------
        $base_path = $this->config->item('pdln_upload_path');
        $update_date = $result_data->update_date;
        $create_date = $result_data->create_date;

        $month = date('m', $create_date);
        $year = date('Y', $create_date);

        $additional_path = $year . '/' . $month . '/pdln/' . $id_surat . "/";
        $targetPath = $base_path . $additional_path;

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0777, TRUE);
        }

        $filename = "resi_ralat_sm_{$id_surat}_{$update_date}.pdf";
        $fullpath = "{$targetPath}{$filename}";

        if (file_exists($fullpath)){
            send_file_to_browser($fullpath); // this function will exec die() and exit
        }
        // ----------------------------------------------------------------------

        $this->db->select('r_unit_tembusan.Nama');
        $this->db->join('r_unit_tembusan', 'r_unit_tembusan.ID = t_template_unit_tembusan.UnitID');
        $this->db->where('TemplateID', $result_data->format_tembusan);
        $unit_tembusan = $this->db->get('t_template_unit_tembusan')->result();

        $this->db->select('t_log_peserta.id_log_peserta,m_pemohon.nama,m_pemohon.jabatan');
        $this->db->join('m_pemohon', 'm_pemohon.id_pemohon = t_log_peserta.id_pemohon');
        $this->db->where('id_pdln', $id_surat);
        $list_pemohon = $this->db->get('t_log_peserta')->result();

        $this->db->select('m_kegiatan.NamaKegiatan');
        $this->db->where('m_kegiatan.ID', $result_data->id_kegiatan);
        $kegiatan = $this->db->get('m_kegiatan')->row();
        
        $this->db->select('m_pdln.no_sp, m_pdln.tgl_sp');
        $this->db->where('m_pdln.id_pdln', $result_data->id_pdln_lama);
        $pdln_lama = $this->db->get('m_pdln')->row();

        $data = array(
            'title' => "Surat Persetujuan",
            'unit_tembusan' => $unit_tembusan,
            'data_sp' => $result_data,
            'kegiatan' => $kegiatan,
            'data_sp_lama' => $pdln_lama,
            'm_pdf' => $this->load->library('M_pdf'));
        $html = $this->load->view('kotak_surat/v_print_ralat', $data, TRUE);

        $data = array(
            'title' => "Lampiran SP",
            'data_lampiran' => $result_data,
            'list_pemohon' => $list_pemohon,
            'm_pdf' => $this->load->library('M_pdf'));
        $html_lampiran = $this->load->view('kotak_surat/v_print_lampiran_ralat', $data, TRUE);

        $this->load->library('M_pdf');
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html);

        $this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html_lampiran);

        $this->m_pdf->pdf->Output($fullpath, 'F');
        send_file_to_browser($fullpath); // this function will exec die() and exit
    }

    public function print_pembatalan($id_surat) {
        setlocale(LC_ALL, 'id_ID');

        $this->db->select('m_pdln.id_pdln, m_pdln.id_kegiatan, m_pdln.no_sp, m_pdln.tgl_sp, m_pdln.tgl_surat_usulan_fp,
                            m_pdln.no_surat_usulan_fp, m_pdln.pejabat_sign_sp, m_pdln.format_tembusan, m_pdln.id_pdln_lama, m_pdln.create_date, m_pdln.update_date');

        $this->db->where('m_pdln.id_pdln', $id_surat);
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

        $filename = "resi_batal_sm_{$id_surat}_{$update_date}.pdf";
        $fullpath = "{$targetPath}{$filename}";

        if (file_exists($fullpath)){
            send_file_to_browser($fullpath); // this function will exec die() and exit
        }
        // ----------------------------------------------------------------------

        $this->db->select('r_unit_tembusan.Nama');
        $this->db->join('r_unit_tembusan', 'r_unit_tembusan.ID = t_template_unit_tembusan.UnitID');
        $this->db->where('TemplateID', $result_data->format_tembusan);
        $unit_tembusan = $this->db->get('t_template_unit_tembusan')->result();

        $this->db->select('t_log_peserta.id_log_peserta,m_pemohon.nama,m_pemohon.jabatan');
        $this->db->join('m_pemohon', 'm_pemohon.id_pemohon = t_log_peserta.id_pemohon');
        $this->db->where('id_pdln', $id_surat);
        $list_pemohon = $this->db->get('t_log_peserta')->result();

        $this->db->select('m_kegiatan.NamaKegiatan');
        $this->db->where('m_kegiatan.ID', $result_data->id_kegiatan);
        $kegiatan = $this->db->get('m_kegiatan')->row();
        
        $this->db->select('m_pdln.no_sp, m_pdln.tgl_sp');
        $this->db->where('m_pdln.id_pdln', $result_data->id_pdln_lama);
        $pdln_lama = $this->db->get('m_pdln')->row();

        $data = array(
            'title' => "Surat Persetujuan",
            'unit_tembusan' => $unit_tembusan,
            'data_sp' => $result_data,
            'kegiatan' => $kegiatan,
            'data_sp_lama' => $pdln_lama,
            'm_pdf' => $this->load->library('M_pdf'));
        $html = $this->load->view('kotak_surat/v_print_pembatalan', $data, TRUE);
        /*
          $data = array(
          'title' => "Lampiran SP",
          'data_lampiran' => $result_data,
          'list_pemohon' => $list_pemohon,
          'm_pdf' => $this->load->library('M_pdf'));
          $html_lampiran = $this->load->view('kotak_surat/v_print_lampiran_pembatalan', $data, TRUE);
         */
        $this->load->library('M_pdf');
        $this->m_pdf->pdf->AddPage('P', // L - landscape, P - portrait
                '', '', '', '', 15, // margin_left
                15, // margin right
                15, // margin top
                10, // margin bottom
                18, // margin header
                5); // margin footer
        $this->m_pdf->pdf->WriteHTML($html);
        /*
          $this->m_pdf->pdf->AddPage('L', // L - landscape, P - portrait
          '', '', '', '', 15, // margin_left
          15, // margin right
          15, // margin top
          10, // margin bottom
          18, // margin header
          5); // margin footer
          $this->m_pdf->pdf->WriteHTML($html_lampiran);
         */
        $this->m_pdf->pdf->Output($fullpath, 'F');
        
        send_file_to_browser($fullpath); // this function will exec die() and exit
    }

    public function get_list_peserta() {
        $id_pdln = $this->uri->segment(4);
        $this->crud_ajax->init('t_log_peserta', 'id_log_peserta', NULL);
        $join = array(
            'm_pemohon' => array('m_pemohon.id_pemohon = t_log_peserta.id_pemohon', 'left'),
            'r_institution' => array('m_pemohon.id_instansi = r_institution.ID', 'left'),
        );
        $this->crud_ajax->setJoinField($join);
        $this->crud_ajax->set_select_field('id_log_peserta,m_pemohon.id_pemohon,id_kategori_biaya,id_biaya,nik,nip_nrp,m_pemohon.nama nama_peserta,jabatan,start_date,end_date,r_institution.Nama,m_pemohon.id_instansi,instansi_lainnya');
        $where_data = array(
            'id_pdln' => $id_pdln
        );
        $this->crud_ajax->setExtraWhere($where_data);

        $list = $this->crud_ajax->get_datatables();
        $data = array();
        if (isset($_POST['start'])) {
            $no = $_POST['start'];
        } else {
            $no = 0;
        }
        foreach ($list as $peserta) {
            $no++;
            $row = array();
            $row[] = $peserta->id_log_peserta;
            $row[] = $no . '.';
            $row[] = $peserta->nip_nrp;
            $row[] = $peserta->nik;
            $row[] = $peserta->nama_peserta;
            $row[] = ucwords($peserta->jabatan);
            $row[] = (empty($peserta->start_date) || empty($peserta->end_date)) ? '' : day(date("Y-m-d", $peserta->start_date)) . ' s.d ' . day(date("Y-m-d", $peserta->end_date));
            $row[] = ($peserta->id_kategori_biaya == 1) ? "Campuran" : "Tunggal";
            $row[] = ((empty($peserta->id_instansi)) OR ($peserta->id_instansi) == 0 ) ? $peserta->instansi_lainnya : $peserta->Nama;

            $id_peserta = $peserta->id_log_peserta;
            $result = $this->db->get_where('view_biaya_log_peserta', array("id_log_peserta" => $id_peserta));
            if ($result->num_rows() > 0) {
                $biaya;
                foreach ($result->result() as $value) {
                    $biaya = $value->biaya;
                }
                $row[] = (empty($biaya)) ? '' : 'Rp. ' . number_format(intval($biaya));
            } else {
                $row[] = '';
            }
            $row[] = '	<button type="button" id="edit_peserta" title="Edit" class="btn btn-xs purple"><i class="fa fa-edit"></i>&nbsp; Edit </button>
                        <button type="button" id="remove_peserta" title="Hapus" class="btn btn-xs red"><i class="fa fa-remove"></i>&nbsp; Hapus</button>';
            $data[] = $row;
        }
        $output = array(
            "draw" => (isset($_POST['draw']) ? $_POST['draw'] : NULL),
            "recordsTotal" => $this->crud_ajax->count_filtered(),
            "recordsFiltered" => $this->crud_ajax->count_filtered(),
            "data" => $data,
            "query" => $this->db->last_query()
        );
        echo json_encode($output);
    }

    public function get_label_penandatangan($id_user, $level_pejabat) {
        $this->db->select('*');
        $this->db->where('m_user.UserID', $id_user);
        $user = $this->db->get('m_user')->row();

        $label = "";

        if (isset($user->level)) {

            if (($user->level == LEVEL_KARO) && ($level_pejabat == LEVEL_ESELON_II)) {
                $label = "a.n.	Sekretaris Kementerian Sekretariat Negara <br/>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala Biro Kerja Sama Teknik Luar Negeri";
            } else if (($user->level == LEVEL_KARO) && ($level_pejabat == LEVEL_ESELON_I)) {
                $label = "Plh. Sekretaris Kementerian Sekretariat Negara <br/>";
            } else if (($user->level == LEVEL_KABAG)) {
                $label = "a.n. Sekretaris Kementerian Sekretariat Negara <br/>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Plh.Kepala Biro Kerja Sama Teknik Luar Negeri";
            } else if (($user->level == LEVEL_SESMEN) && ($level_pejabat == LEVEL_ESELON_I)) {
                $label = "   Sekretaris Kementerian Sekretariat Negara";
            } else if (($user->level == LEVEL_SESMEN) && ($level_pejabat == LEVEL_MENTERI)) {
                $label = "a.n. Menteri Sekretaris Negara <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sekretaris Kementerian Sekretariat Negara";
            } else if (($user->level == LEVEL_MENSESNEG)) {
                $label = "Menteri Sekretaris Negara <br/>";
            }
        }

        return $label;
    }

    public function get_detail_tembusan() {
        $template_tembusan = $this->input->post('template_tembusan');

        if ($template_tembusan === "0" || !isset($template_tembusan)) {
            $data['status'] = FALSE;
            $data['message'] = "Silahkan pilih salah satu format tembusan terlebih dahulu";
            echo json_encode($data);
            exit;
        } else {
            $data['status'] = TRUE;
            $data['nama_format'] = $this->db->where('ID', $template_tembusan)->get('r_template_tembusan')->row()->Nama;
            $data['message'] = "";

            $this->db->select('*');
            $this->db->from('t_template_unit_tembusan');
            $this->db->where('TemplateID', $template_tembusan);

            $query = $this->db->get();
            $no = 1;
            foreach ($query->result() as $row) {
                $data['message'] = $data['message'] . "<br/>" . $no . ". " . $this->db->where('ID', $row->UnitID)->get('r_unit_tembusan')->row()->Nama;
                $no++;
            }

            $data['message'] = $data['message'] . "<br/>" . $no . ". Yang Bersangkutan";

            echo json_encode($data);
        }
    }

}
