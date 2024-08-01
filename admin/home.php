<h1 class="">Welcome to <?php echo $_settings->info('name') ?></h1>
<style>
  #cover-image {
    width: calc(100%);
    height: 50vh;
    object-fit: cover;
    object-position: center center;
  }
</style>
<hr>
<div class="row">
  <div class="col-12 col-sm-6 col-md-6">
    <div class="info-box">
      <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-th-list"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Total Collections </span>
        <span class="iinfo-box-number text-right h4">
          <?php
          $total = $conn->query("SELECT count(id) as total FROM category_list where delete_flag = 0 ")->fetch_assoc()['total'];
          echo format_num($total);
          ?>
          <?php ?>
        </span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <!-- <div class="col-12 col-sm-6 col-md-6">
    <div class="info-box">
      <span class="info-box-icon bg-gradient-secondary elevation-1"><i class="fas fa-table"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Total Phases</span>
        <span class="iinfo-box-number text-right h4">
          <?php
          //$total = $conn->query("SELECT count(id) as total FROM phase_list where delete_flag = 0 ")->fetch_assoc()['total'];
          //echo format_num($total);
          ?>
          <?php ?>
        </span>
      </div>
      <!-- /.info-box-content -->
  <!-- </div> -->
  <!-- /.info-box -->
  <!-- </div> -->
  <div class="col-12 col-sm-6 col-md-6">
    <div class="info-box">
      <span class="info-box-icon bg-gradient-light border elevation-1"><i class="fas fa-user-friends"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Std</span>
        <span class="iinfo-box-number text-right h4">
          <?php
          $total = $conn->query("SELECT count(id) as total FROM member_list where delete_flag = 0 and status = 1 ")->fetch_assoc()['total'];
          echo format_num($total);
          ?>
          <?php ?>
        </span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
  <div class="col-12 col-sm-6 col-md-6">
    <div class="info-box">
      <span class="info-box-icon bg-gradient-warning border elevation-1"><i class="fas fa-coins"></i></span>

      <div class="info-box-content">
        <span class="info-box-text">Total Collection This Month</span>
        <span class="iinfo-box-number text-right h4">
          <?php
          $total = $conn->query("SELECT sum(total_amount) as total FROM collection_list where date_format(date_collected,'%Y-%m') = '" . date('Y-m') . "' ")->fetch_assoc()['total'];
          $total = $total > 0 ? $total : 0;
          echo format_num($total);
          ?>
          <?php ?>
        </span>
      </div>
      <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
  </div>
</div>

<div class="clear-fix mb-2">
  <div class="text-center w-100">
    <img src="<?= validate_image($_settings->info('cover')) ?>" alt="System Cover image" class="w-100" id="cover-image">
  </div>
</div>