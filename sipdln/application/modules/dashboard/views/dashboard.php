<div class="row">
    <div class="col-md-12">
        <!-- BEGIN DASHBOARD PORTLET-->
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-eye font-green-haze"></i>
                    <span class="caption-subject bold uppercase font-green-haze"> 
                        Monitoring Progres Layanan PDLN
                    </span>
                </div>
                <div class="tools">
                    <a href="javascript:;" class="collapse"> </a>
                    <a href="javascript:;" class="reload"> </a>
                    <a href="javascript:;" class="fullscreen"> </a>                    
                </div>
            </div>
            <div class="portlet-body">
                <div class="table">
                    <table class="table table-hover table-bordered" id="tabel_dashboard">
                        <thead>
                            <tr>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">ID PDLN</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">No.</th>                                
                                <th class="text-center" width="8%" style = "vertical-align: middle;">No. Register</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">Tgl. Register</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">No. SP</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">Tgl. SP</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">Status</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">Tgl. Update</th>
                                <th class="text-center" width="7%" style = "vertical-align: middle;">No. Surat FP</th>
                                <th class="text-center" width="7%" style = "vertical-align: middle;">No. Surat Pemohon</th>
                                <th class="text-center" width="8%" style = "vertical-align: middle;">Jenis Permohonan</th>
                                <th class="text-center" width="15%" style = "vertical-align: middle;">Jenis Kegiatan</th>                                
                                <th class="text-center" width="10%" style = "vertical-align: middle;">Catatan</th>
                                <th class="text-center" width="15%" style = "vertical-align: middle;">Aksi</th>
                                <th class="text-center" width="4%" style = "vertical-align: middle;">Unduh SP</th>                                
                            </tr>
                        </thead>
                        <tbody class="text-center">                        
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- END DASHBOARD PORTLET-->
    </div>	
</div>
<!-- BEGIN MODAL LOG CATATAN --> 
<div class="modal fade" id="modal_log_catatan" name="modal_log_catatan" role="dialog" tabindex="-1" aria-labelledby="modal_log_catatan" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="modal-title">
                    <h3 class="font-blue-madison uppercase bold text-center">
                        <span class="title-text"><i class="fa fa-history"> </i> Log Catatan Pengembalian</span>
                    </h3>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="portlet light bordered">
                            <div class="portlet-title">
                                <div class="caption caption-md">
                                    <i class="icon-bar-chart font-dark hide"></i>
                                    <span class="caption-subject font-dark bold uppercase"><i class="fa fa-th-list"> </i> List Catatan</span>
                                    <span class="caption-helper">
                                        <span class="badge badge-success total_catatan"></span>
                                    </span>
                                </div>            
                            </div>
                            <div class="portlet-body">
                                <div class="scroller" style="height: 160px;" data-always-visible="1" data-rail-visible1="0" data-handle-color="#D7DCE2">
                                    <div class="general-item-list" id="general-item-list">
                                    <!-- AUTOMATE VIA JQUERY -->
                                    </div>                                
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" class="btn blue-dark"> <i class="fa fa-left"> </i> Kembali </button>
            </div>
        </div>          
    </div>
</div>
<!-- END MODAL LIST DATA PERMOHONAN -->        
<!-- END ROW -->
<!-- Add mousewheel plugin (this is optional) -->
<script type="text/javascript" src="<?php echo base_url(); ?>assets/global/plugins/fancybox/lib/jquery.mousewheel-3.0.6.pack.js"></script>
<!-- Add fancyBox -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/jquery.fancybox.css" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/jquery.fancybox.pack.js"></script>

<!-- Optionally add helpers - button, thumbnail and/or media -->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/helpers/jquery.fancybox-buttons.css" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/helpers/jquery.fancybox-buttons.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/global/plugins/fancybox/source/helpers/jquery.fancybox-media.js"></script>
<script src="<?php echo base_url(); ?>assets/custom/scripts/dashboard.js" type="text/javascript"></script>