<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->
    <head>
        <meta charset="utf-8" />
        <title>PDLN | <?php echo $title; ?> </title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="KTLN Perjalanan Dinas Luar Negeri" name="description" />
        <meta content="Biro KTLN" name="author" /> 
        <script type="text/javascript">
            var BASE_URL = '<?php echo base_url(); ?>';
        </script>
        <!-- BEGIN GLOBAL MANDATORY STYLES -->        
        <link href="<?php echo base_url(); ?>assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css" />
        <!-- END GLOBAL MANDATORY STYLES -->        
        <!-- BEGIN THEME GLOBAL STYLES -->
        <link href="<?php echo base_url(); ?>assets/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
        <!-- END THEME GLOBAL STYLES -->
        <!-- BEGIN PAGE LEVEL STYLES -->
        <link href="<?php echo base_url(); ?>assets/pages/css/login.min.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL STYLES -->
        <!-- BEGIN THEME LAYOUT STYLES -->
        <!-- END THEME LAYOUT STYLES -->
        <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/psd/logo-sesneg.png" /> 
    <!-- END HEAD -->
    <body class=" login" style="background-image:url(<?php echo base_url(); ?>assets/pages/img/login/bg1.jpg);background-size:cover;">        
        <!-- BEGIN LOGIN -->
        <div class="content" style="margin-top: 40px;background: rgba(255,255,255,0.80);border-radius: 8px 8px 8px 8px;padding: 10px 30px 20px;">            
			<!-- BEGIN LOGO -->
			<div class="logo">
				<img src="<?php echo base_url(); ?>assets/pages/img/logo-sesneg.png" alt="" style="margin-top: -50px;"/>  
			</div>
			<!-- END LOGO -->
			<div class="title-apps" style="font-family: proxima_nova_rgbold;font-size: 28pt;text-align: center;width: auto;color: #1ba1e2;margin: 0 0 0 0;line-height: 1;">
				SIKTLN
			</div>
			<div class="desc-apps" style="margin: 0 0 0 0;padding-bottom: 10px;font-family: proxima_novasemibold;font-size: 13pt;text-align: center;width: auto;color: #1ba1e2;">
				Login Perjalanan Dinas Luar Negeri
			</div>			
			<!-- BEGIN LOGIN FORM -->
            <form action="javascript:;" class="login-form" method="post">                  
                <div class="alert alert-danger display-hide">
                    <button class="close" data-close="alert"></button>
                    <span>Lengkapi username dan password anda. </span>
                </div>
				
				<div class="alert alert-warning display-hide">
                    <button class="close" data-close="alert"></button>
                    <span></span>
                </div>
				
				<div class="alert alert-success display-hide">
                    <button class="close" data-close="alert"></button>
                    <span></span>
                </div>			
                <div class="form-group">
                    <!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->
                    <label class="control-label visible-ie8 visible-ie9">Username</label>
                    <input class="form-control form-control-solid placeholder-no-fix" type="text" autocomplete="off" placeholder="Username" name="identity" id="identity" /> 
				</div>
                <div class="form-group">
                    <label class="control-label visible-ie8 visible-ie9">Password</label>
                    <input class="form-control form-control-solid placeholder-no-fix" type="password" autocomplete="off" placeholder="Password" name="password" id="password" /> 
				</div>
				<div class="form-group">                    
                    <div class="col-md-4">
                        <?php //echo $this->recaptcha->render(); ?>
                    </div>                    
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn green uppercase" style="background-color: #1ba1e2;width: 100%;">Login</button>
                </div>
            </form> 
            <!-- END LOGIN FORM -->            
        </div>
        <div class="copyright"> </div>
        <!--[if lt IE 9]>
        <script src="<?php echo base_url(); ?>assets/global/plugins/respond.min.js"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/excanvas.min.js"></script> 
        <script src="<?php echo base_url(); ?>assets/global/plugins/ie8.fix.min.js"></script> 
        <![endif]-->
        <!-- BEGIN CORE PLUGINS -->
        <script src="<?php echo base_url(); ?>assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="<?php echo base_url(); ?>assets/global/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
        <script src="<?php echo base_url(); ?>assets/global/plugins/jquery-validation/js/additional-methods.min.js" type="text/javascript"></script>        
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="<?php echo base_url(); ?>assets/global/scripts/app.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
         <script src="<?php echo base_url(); ?>assets/custom/scripts/login.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <!-- END THEME LAYOUT SCRIPTS -->
    <!-- Google Code for Universal Analytics -->
	<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-90021923-1', 'auto');
  ga('send', 'pageview');

</script>
<!-- End -->
</body>
</html>