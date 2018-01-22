var HandlePerpanjangan = function () {
    var initTable = function () {
        var table = $('#tabel_perpanjangan'),
           save_method, row,form = $('#form_perpanjangan'),
            error = $('.alert-danger', form);        
        var oTable = table.dataTable({
                    serverSide: true,
                    processing: true,
                    searching: true,
                    ordering : true,            
                    info:true,
                    paging : true, 
                    language: {
                        url: BASE_URL+"/assets/global/plugins/datatables/plugins/i18n/Indonesian-Alternative.json"
                    },
                    ajax: {
                        url: BASE_URL+"layanan/perpanjangan/perpanjangan_list", // ajax source  
                        type: 'POST'
                    },                    
                    lengthMenu: [
                        [5, 10, 20, -1],
                        [5, 10, 20, "Semua"] // change per page values here
                    ],
                    pageLength: 10, 
                    columnDefs: [
                        { visible: false, targets: 0 , orderable: false , searchable : false},
                        { visible: true, targets: 7 , orderable: false , searchable : false}
                    ],
        });
        $("#tgl_surat_usulan,#tgl_mulai,#tgl_akhir").datepicker();        
        $("#file_surat_usulan_fc,#file_surat_usulan,#file_surat_2_1,#file_surat_2_2,#file_surat_2_3,#file_surat_2_4,#file_surat_2_5,#file_surat_2_6").fileinput({
            'language' : 'id',
            'showPreview' : true,
            'allowedFileExtensions' : ['jpg', 'png','gif','docx','doc','pdf'],
            'elErrorContainer': '#errorBlock',
            'maxFileSize': 1000,
            'maxFilesNum': 1
        });
        $("#file_pas_foto,#file_karpeg,#file_ktp,#file_npwp").fileinput({
            'language' : 'id',
            'showPreview' : true,
            'allowedFileExtensions' : ['jpg', 'png','gif'],
            'elErrorContainer': '#errorBlock',
            'maxFileSize': 300,
            'maxFilesNum': 1
        });
        $("#nip_peserta").maxlength({
            threshold: 18,
            warningClass: "label label-danger",
            limitReachedClass: "label label-info",
            placement: 'top',
            validate: true
        });
        $("#nik_peserta").maxlength({
            threshold: 16,
            warningClass: "label label-danger",
            limitReachedClass: "label label-info",
            placement: 'top',
            validate: true
        });
    };
    return {
        //main function to initiate the module
        init: function () {            
            initTable();
        }
    };
}();
if (App.isAngularJsApp() === false) { 
    jQuery(document).ready(function() {
        HandlePerpanjangan.init();
    });
}