<div>
  <h3>Feedback</h3>
  <table class="table">
    <thead>
      <tr>
        <th class="text-center">Feedback Number</th>
        <th class="text-center">Fullname</th>
        <th class="text-center">Lastname</th>
        <th class="text-center">Email</th>
        <th class="text-center">Contact Number</th>
        <th class="text-center">Feedback</th>
      </tr>
    </thead>
    <?php
      include_once "../config/dbconnect.php";
      $sql = "SELECT * FROM feedback";
      $result = $conn->query($sql);
      $count = 1;
      while ($row = $result->fetch_assoc()) {
    ?>
    <tr>
      <td><?= $count++ ?></td>
      <td><?= $row["fname"] ?></td>
      <td><?= $row["lname"] ?></td>
      <td><?= $row["email"] ?></td>
      <td><?= $row["mobile"] ?></td>
      <td><?= $row["message"] ?></td>
    </tr>
    <?php
            $count = $count + 1;
          }
      ?>
  </table>
</div>