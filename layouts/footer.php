     </div>
    </div>

    <div id="appConfirmModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="appConfirmLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="appConfirmLabel">Konfirmasi</h4>
          </div>
          <div class="modal-body" id="appConfirmMessage"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" id="appConfirmButton">Ya, Lanjutkan</button>
          </div>
        </div>
      </div>
    </div>

    <div id="appAlertModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="appAlertLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="appAlertLabel">Pemberitahuan</h4>
          </div>
          <div class="modal-body" id="appAlertMessage"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal" id="appAlertButton">Mengerti</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap.min.js"></script>
  <script type="text/javascript" src="libs/js/functions.js"></script>
  </body>
</html>

<?php if(isset($db)) { $db->db_disconnect(); } ?>
