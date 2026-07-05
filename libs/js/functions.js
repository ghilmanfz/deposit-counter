
function suggetion() {

     $('#sug_input').keyup(function(e) {
         $('#selected_product_id').val('');

         var formData = {
             'product_name' : $('input[name=title]').val()
         };

         if(formData['product_name'].length >= 1){

           // process the form
           $.ajax({
               type        : 'POST',
               url         : 'ajax.php',
               data        : formData,
               dataType    : 'json',
               encode      : true
           })
               .done(function(data) {
                   //console.log(data);
                   $('#result').html(data).fadeIn();
                   $('#result li').click(function() {

                                         $('#sug_input').val($(this).attr('data-product-title'));
                                         $('#selected_product_id').val($(this).attr('data-product-id'));
                     $('#result').fadeOut(500);

                   });

                   $("#sug_input").blur(function(){
                     $("#result").fadeOut(500);
                   });

               });

         } else {

           $("#result").hide();

         };

         e.preventDefault();
     });

 }
  $('#sug-form').submit(function(e) {
      var formData = {
          'p_name' : $('input[name=title]').val(),
          'p_id' : $('#selected_product_id').val()
      };
        // process the form
        $.ajax({
            type        : 'POST',
            url         : 'ajax.php',
            data        : formData,
            dataType    : 'json',
            encode      : true
        })
            .done(function(data) {
                //console.log(data);
                $('#product_info').html(data).show();
                total();
                $('.datePicker').datepicker('update', new Date());

            }).fail(function() {
                $('#product_info').html(data).show();
            });
      e.preventDefault();
  });
  function total(){
    $('#product_info input').change(function(e)  {
            var price = +$('input[name=price]').val() || 0;
            var qty   = +$('input[name=quantity]').val() || 0;
            var total = qty * price ;
                $('input[name=total]').val(total.toFixed(2));
    });
  }

  $(document).ready(function() {

    //tooltip
    $('[data-toggle="tooltip"]').tooltip();

    $('.submenu-toggle').click(function () {
       $(this).parent().children('ul.submenu').toggle(200);
    });
    //suggetion for finding product names
    suggetion();
    // Callculate total ammont
    total();

    $('.datepicker')
        .datepicker({
            format: 'yyyy-mm-dd',
            todayHighlight: true,
            autoclose: true
        });
    initPhotoPreview();

    // Inisialisasi DataTables untuk semua tabel dengan kelas .table-bordered
    if ($.fn.DataTable) {
      var dtables = $('table.table-bordered').DataTable({
        language: {
          url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
        },
        scrollX: true,      // geser horizontal saat kolom banyak (tidak terpotong)
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
      });
      // Ratakan ulang lebar kolom setelah bahasa (async) selesai & saat resize
      $(window).on('resize', function(){ dtables.columns.adjust(); });
      setTimeout(function(){ dtables.columns.adjust(); }, 400);
    }
  });

  function initPhotoPreview(){
    $(document).on('change', 'input.photo-input', function(){
      var $container = $(this).closest('.form-group').find('.photo-preview');
      var files = this.files;
      var html = '';
      if(files && files.length){
        html += '<ul class="list-unstyled" style="margin:0; padding-left:0;">';
        for(var i = 0; i < files.length; i++){
          html += '<li><strong>' + files[i].name + '</strong> (' + Math.round(files[i].size/1024) + ' KB)</li>';
        }
        html += '</ul>';
      } else {
        html = 'Belum ada file dipilih.';
      }
      $container.html(html);
    });
  }

  // Konfirmasi semua aksi hapus menggunakan modal sistem, bukan browser confirm Chrome.
  var deleteLinkUrl = null;
  $(document).on('click', 'a[href*="delete_"]', function(e) {
    e.preventDefault();
    deleteLinkUrl = $(this).attr('href');
    var deleteTitle = $(this).attr('title') || 'Hapus data ini';
    $('#deleteConfirmModal .modal-title').text(deleteTitle);
    $('#deleteConfirmModal .modal-body').text('Apakah Anda yakin ingin menghapus data ini?');
    $('#deleteConfirmModal').modal('show');
  });

  $('#confirmDeleteBtn').on('click', function() {
    if(deleteLinkUrl){
      window.location.href = deleteLinkUrl;
    }
  });
