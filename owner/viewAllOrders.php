<div id="ordersBtn" >
  <h2>Manage Orders</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Order ID.</th>
        <th>Customer</th>
        <th>Reference Number</th>
        <th>Categories</th>
        <th>Order Items</th>
        <th>Order Quantity</th>
        <th>Total Amount</th>
        <th>Ordered Date & Time</th>
        <th>Payment Method</th>
        <th>Order Status</th>
        <th>Payment Status</th>
     </tr>
    </thead>
    <?php
      include_once "../config/dbconnect.php";
      $sql = "SELECT * FROM orders ORDER BY date_ordered DESC, time_ordered DESC";
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
    ?>
      <tr>
        <td><?=$row["order_id"]?></td>
        <td><?=$row["name"]?></td>
        <td><?=$row["reference_number"]?></td>
        <td><?=$row["cat_name"]?></td>
        <td><?=$row["item_name"]?></td>
        <td><?=$row["order_qty"]?></td>
        <td><?=$row["total_amount"]?></td>
        <td><?=$row["date_ordered"] . ' ' . $row["time_ordered"]?></td>
        <td><?=$row["payment_method"]?></td>
        <?php 
          if($row["order_status"]=='C'){             
        ?>
          <td><button class="btn btn-danger" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Confirmed</button></td>
        <?php       
          } else if($row["order_status"]=='PK'){
        ?>
          <td><button class="btn btn-success" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Packed</button></td>
        <?php    
          } else if($row["order_status"]=='P'){
        ?>
          <td><button class="btn btn-success" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Pending</button></td>
        <?php    
          } else if($row["order_status"]=='D'){
        ?>
          <td><button class="btn btn-success" onclick="ChangeOrderStatus('<?=$row['order_id']?>')">Delivered</button></td>
        <?php
          }
          if($row["payment_status"]=='C'){
        ?>
          <td><button class="btn btn-danger"  onclick="ChangePay('<?=$row['order_id']?>')">Completed</button></td>
        <?php                        
          } else if($row["payment_status"]=='I'){
        ?>
          <td><button class="btn btn-success" onclick="ChangePay('<?=$row['order_id']?>')">Incomplete </button></td>
        <?php
          }
        ?>
      </tr>
    <?php
        }
      }
    ?>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="viewModal" role="dialog">
  <div class="modal-dialog modal-lg">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Order Details</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="order-view-modal modal-body">
      </div>
    </div><!--/ Modal content-->
  </div><!-- /Modal dialog-->
</div>

<script>
  //for view order modal  
  $(document).ready(function(){
    $('.openPopup').on('click',function(){
      var dataURL = $(this).attr('data-href');
      $('.order-view-modal').load(dataURL,function(){
        $('#viewModal').modal({show:true});
      });
    });
  });
</script>
