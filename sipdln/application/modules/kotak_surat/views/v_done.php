<link rel="stylesheet" href="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/jquery.fancybox.css" /> 

<div class="row">
    <div class="col-md-12"> 
        <!-- BEGIN CHART PORTLET-->
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-eye font-green-haze"></i>
                    <span class="caption-subject bold uppercase font-green-haze"> 
                        Permohonan Selesai Diproses
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
                <div class="table">
                    <table class="table table-hover table-bordered" id="tabel_done_manage">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nomor Register</th>
                                <th>Tanggal Register</th>
								<th>Nomor Surat FP</th>  
								<th>Unit Pemohon</th>
								<th>Unit Focal Point</th>
								<th class="text-center">Jenis Permohonan</th>
								<th>Jenis Kegiatan</th>
								<th class="text-center">Status</th> 
                                <th class="text-center">Aksi</th>
								<th class="text-center">Unduh SP</th> 
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
<script src="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/jquery.fancybox.js"></script>
<script src="<?php echo base_url(); ?>assets/custom/scripts/kotak_surat/done.js?_dt=201706211600" type="text/javascript"></script>