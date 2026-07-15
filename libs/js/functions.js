
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

  // Dialog aplikasi terpusat untuk seluruh konfirmasi form dan tautan.
  var pendingAppConfirmation = null;

  function allowedConfirmButtonClass(buttonClass){
    var allowed = ['btn-primary','btn-danger','btn-warning','btn-success','btn-info'];
    return allowed.indexOf(buttonClass) !== -1 ? buttonClass : 'btn-primary';
  }

  function showAppConfirmation($source, pending){
    var title = $source.attr('data-confirm-title') || $source.attr('title') || 'Konfirmasi';
    var message = $source.attr('data-app-confirm') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    var buttonText = $source.attr('data-confirm-button') || 'Ya, Lanjutkan';
    var buttonClass = allowedConfirmButtonClass($source.attr('data-confirm-class') || 'btn-primary');

    pendingAppConfirmation = pending;
    $('#appConfirmLabel').text(title);
    $('#appConfirmMessage').text(message);
    $('#appConfirmButton')
      .removeClass('btn-primary btn-danger btn-warning btn-success btn-info')
      .addClass(buttonClass)
      .text(buttonText);
    $('#appConfirmModal').modal('show');
  }

  function findFormSubmitter(event, form){
    var originalEvent = event.originalEvent || null;
    if(originalEvent && originalEvent.submitter){ return originalEvent.submitter; }
    var active = document.activeElement;
    if(active && active.form === form && /^(?:button|input)$/i.test(active.tagName)){ return active; }
    return $(form).find('button[type="submit"],input[type="submit"],button:not([type])').get(0) || null;
  }

  function submitConfirmedForm(form, submitter){
    $(form).data('app-confirm-bypass', true);
    if(typeof form.requestSubmit === 'function'){
      if(submitter){ form.requestSubmit(submitter); }
      else { form.requestSubmit(); }
      return;
    }

    if(submitter && submitter.name){
      $('<input>', {
        type: 'hidden',
        name: submitter.name,
        value: submitter.value || ''
      }).appendTo(form);
    }
    form.submit();
  }

  $(document).on('submit', 'form[data-app-confirm]', function(event){
    var $form = $(this);
    if($form.data('app-confirm-bypass')){
      $form.removeData('app-confirm-bypass');
      return;
    }
    event.preventDefault();
    showAppConfirmation($form, {
      type: 'form',
      form: this,
      submitter: findFormSubmitter(event, this)
    });
  });

  $(document).on('click', 'a[data-app-confirm],button[data-app-confirm],input[data-app-confirm],a[href*="delete_"]', function(event){
    var $source = $(this);
    if($source.data('app-confirm-bypass')){
      $source.removeData('app-confirm-bypass');
      return;
    }
    if(this.disabled || $source.hasClass('disabled')){ return; }
    if(this.form && typeof this.form.checkValidity === 'function' && !this.form.checkValidity()){
      return;
    }

    event.preventDefault();
    if(this.tagName.toLowerCase() === 'a'){
      showAppConfirmation($source, { type: 'link', href: $source.attr('href') });
    } else {
      showAppConfirmation($source, { type: 'control', control: this });
    }
  });

  $('#appConfirmButton').on('click', function(){
    var pending = pendingAppConfirmation;
    pendingAppConfirmation = null;
    $('#appConfirmModal').modal('hide');
    if(!pending){ return; }

    if(pending.type === 'form'){
      submitConfirmedForm(pending.form, pending.submitter);
    } else if(pending.type === 'link' && pending.href){
      window.location.assign(pending.href);
    } else if(pending.type === 'control' && pending.control){
      $(pending.control).data('app-confirm-bypass', true);
      pending.control.click();
    }
  });

  $('#appConfirmModal').on('hidden.bs.modal', function(){
    pendingAppConfirmation = null;
  });

  window.showAppAlert = function(message, options){
    options = options || {};
    $('#appAlertLabel').text(options.title || 'Pemberitahuan');
    $('#appAlertMessage').text(message || '');
    $('#appAlertButton').text(options.buttonText || 'Mengerti');
    $('#appAlertModal').modal('show');
  };
