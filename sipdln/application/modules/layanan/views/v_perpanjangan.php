<div class="row">
    <div class="col-md-12"> 
        <!-- BEGIN CHART PORTLET-->
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-eye font-green-haze"></i>
                    <span class="caption-subject bold uppercase font-green-haze"> 
                        Daftar Perpanjangan/Ralat 
                    </span>
                </div>
                <div class="tools">
                    <a href="javascript:;" class="collapse"> </a>
                    <a href="#portlet-config" data-toggle="modal" class="config"> </a>
                    <a href="javascript:;" class="reload"> </a>
                    <a href="javascript:;" class="fullscreen"> </a>
                    <a href="javascript:;" class="remove"> </a>
                </div>
            </div>
            <div class="portlet-body">
                <?php if ($this->ion_auth->is_allowed(26, 'create')) {
                    ?>
                    <div class="actions">  
                        <a href="<?php echo base_url(); ?>layanan/perpanjangan/add" class="nav-link ">
                            <button class="btn btn-outline blue"> <i class="fa fa-plus"></i> Tambah Perpanjangan / Ralat Baru </button> 	
                        </a>
                    </div><br/><br/>
                <?php } ?>
                <div class="table">
                    <table class="table table-hover table-bordered" id="tabel_perpanjangan">
                        <thead>
                            <tr>
                                <th>ID PDLN</th>
                                <th>No.</th>
                                <th>Nomor Register</th>
                                <th>Tanggal Register</th>
                                <th>Nomor Surat</th>
                                <th>Jenis Permohonan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- END CHART PORTLET-->                    
    </div>
</div>
<!-- END ROW --> 
<script src="<?php echo base_url(); ?>assets/custom/scripts/layanan/perpanjangan.js" type="text/javascript"></script>