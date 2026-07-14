<?php
  $page_title = 'Pengambilan Barang';
  require_once('includes/load.php');
  // Checkin What level user has permission to view this page
   require_permission('transaksi','view');
?>
<?php
$sales = find_all_sale();
$msg = $session->msg();
?>
<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
  </div>
</div>
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading clearfix">
          <strong>
            <span class="glyphicon glyphicon-th"></span>
            <span>Pengambilan Barang</span>
          </strong>
          <?php if(role_can_action('transaksi','create')): ?>
            <div class="pull-right">
              <a href="add_withdrawal.php" class="btn btn-primary">Tambah Pengambilan</a>
            </div>
          <?php endif; ?>
        </div>
        <div class="panel-body">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th> Barang Titipan </th>
                <th> Pelanggan </th>
                <th class="text-center" style="width: 15%;"> Jumlah</th>
                <th class="text-center" style="width: 15%;"> Tanggal </th>
                <th class="text-center" style="width: 100px;"> Aksi </th>
             </tr>
            </thead>
           <tbody>
             <?php foreach ($sales as $sale):?>
             <tr>
               <td class="text-center"><?php echo count_id();?></td>
               <td><?php echo remove_junk($sale['name']); ?></td>
               <td><?php echo !empty($sale['client_name']) ? remove_junk($sale['client_name']) : 'Internal'; ?></td>
               <td class="text-center"><?php echo (int)$sale['qty']; ?> <?php echo !empty($sale['unit_name']) ? remove_junk($sale['unit_name']) : 'satuan data lama'; ?></td>
               <td class="text-center"><?php echo $sale['date']; ?></td>
               <td class="text-center">
                  <div class="btn-group">
                     <?php if(isset($sale['source_type']) && $sale['source_type'] === 'bundle'): ?>
                       <span class="label label-info">Dari Request</span>
                     <?php elseif(role_can_action('transaksi','update')): ?>
                       <a href="edit_withdrawal.php?id=<?php echo (int)$sale['id'];?>" class="btn btn-warning btn-xs"  title="Edit" data-toggle="tooltip">
                         <span class="glyphicon glyphicon-edit"></span>
                       </a>
                     <?php endif; ?>
                     <?php if((!isset($sale['source_type']) || $sale['source_type'] === 'legacy') && role_can_action('transaksi','delete')): ?>
                       <form method="post" action="delete_withdrawal.php" style="display:inline;" onsubmit="return confirm('Hapus pengambilan ini dan kembalikan stok?');">
                         <?php echo warehouse_csrf_field(); ?>
                         <input type="hidden" name="id" value="<?php echo (int)$sale['id']; ?>">
                         <button type="submit" class="btn btn-danger btn-xs" title="Hapus" data-toggle="tooltip">
                           <span class="glyphicon glyphicon-trash"></span>
                         </button>
                       </form>
                     <?php endif; ?>
                  </div>
               </td>
             </tr>
             <?php endforeach;?>
           </tbody>
         </table>
        </div>
      </div>
    </div>
  </div>
<?php include_once('layouts/footer.php'); ?>
