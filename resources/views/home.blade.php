@extends('layouts.app')

@section('content') 
<style>    
  .bg-1 {
      background-color: #0d6efd;
  }

  .bg-2 {
      background-color: #5677fc;
  }

  .bg-3 {
      background-color: #03a9f4;
  }
  .bg-4 {
      background-color: #ffc107 ;
  }
  .bg-5 {
      background-color: #ff9800;
  }
  .bg-6 {
      background-color: #ff5722;
  }
  .bg-7 {
      background-color: #e91e63;
  }
  .bg-8 {
      background-color: #9c27b0;
  }
  .bg-9 {
      background-color: #009688;
  }

  .bg-10 {
      background-color: #259b24;
  }

  .bg-11 {
      background-color: #ff5722;
  }
  .bg-12 {
      background-color: #e51c23 ;
  }
</style>
<div class="container">
    <div class="row" align="center">      
        <h5 class="alert alert-primary text-primary" align="left">
          ข้อมูลผู้ป่วยนอก ณ วันที่ <font style="color:red;">{{DateThai(date('Y-m-d'))}}</font> เวลา: <font style="color:red;"><span id="realtime-clock"></span></font>  
          ทั้งหมด : <font style="color:red;">{{$opd_total}}</font> Visit | 
          ปิดสิทธิ สปสช : <font style="color:red;">{{$endpoint_all}}</font> Visit
          <!-- ปุ่มเรียก Modal -->
          <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#nhsoModal">
            ดึงปิดสิทธิ สปสช.
          </button>
        </h5> 
      <div class="col-sm-3">
          <div class="card text-white bg-1 mb-3" style="max-width: 18rem;" >
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              UCS Visit : ปิดสิทธิ
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$ucs_all}} : {{$ucs_endpoint}}</h1> 
              <p class="card-text">
                  <a href="{{ url('/opd_ucs_all') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>           
            </div>
          </div>
      </div>
      <div class="col-sm-3">
          <div class="card text-white bg-2 mb-3" style="max-width: 18rem;">
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              OFC Visit : ปิดสิทธิ : รูดบัตร 
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$ofc_all}} : {{$ofc_endpoint}} : {{$ofc_edc}} </h1>  
              <p class="card-text">
                  <a href="{{ url('/opd_ofc_all') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>  
            </div>
          </div>
      </div>        
      <div class="col-sm-3">
          <div class="card text-white bg-3 mb-3" style="max-width: 18rem;">
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              ไม่ขอ AuthenCode
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$non_authen}}</h1>
              <p class="card-text">
                  <a href="{{ url('/opd_non_authen') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>  
            </div>
          </div>
      </div>
      <div class="col-sm-3">
          <div class="card text-white bg-4 mb-3" style="max-width: 18rem;">
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              ไม่บันทึกสถานพยาบาลหลัก
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$non_hmain}}</h1>  
              <p class="card-text">
                  <a href="{{ url('/opd_non_hospmain') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>  
            </div>
          </div>
      </div>      
      <div class="col-sm-3">
        <div class="card text-white bg-5 mb-3" style="max-width: 18rem;">
          <div class="card-header">
            <ion-icon name="people-outline"></ion-icon>
            UC Anywhere : ปิดสิทธิ : FDH
          </div>
          <div class="card-body">
            <h1 class="card-title text-center">{{$uc_anywhere}} : {{$uc_anywhere_endpoint}} : {{$uc_anywhere_fdh}}</h1>  
            <p class="card-text">
                <a href="{{ url('/opd_ucs_anywhere') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
            </p>  
          </div>
        </div>
      </div>        
      <div class="col-sm-3">
        <div class="card text-white bg-6 mb-3" style="max-width: 18rem;">
          <div class="card-header">
            <ion-icon name="people-outline"></ion-icon>
            UC บริการเฉพาะ : ปิดสิทธิ : FDH
          </div>
          <div class="card-body">
            <h1 class="card-title text-center">{{$uc_cr}} : {{$uc_cr_endpoint}} : {{$uc_cr_fdh}}</h1>
            <p class="card-text">
                <a href="{{ url('/opd_ucs_cr') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
            </p>  
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="card text-white bg-7 mb-3" style="max-width: 18rem;">
          <div class="card-header">
            <ion-icon name="people-outline"></ion-icon>
            UC แพทย์แผนไทย : ปิดสิทธิ : FDH 
          </div>
          <div class="card-body">
            <h1 class="card-title text-center">{{$uc_healthmed}} : {{$uc_healthmed_endpoint}} : {{$uc_healthmed_fdh}}</h1>  
            <p class="card-text">
                <a href="{{ url('/opd_ucs_healthmed') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
            </p>  
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="card text-white bg-8 mb-3" style="max-width: 18rem;" >
          <div class="card-header">
            <ion-icon name="people-outline"></ion-icon>
            PPFS : ปิดสิทธิ : FDH
          </div>
          <div class="card-body">
            <h1 class="card-title text-center">{{$ppfs}} : {{$ppfs_endpoint}} : {{$ppfs_fdh}}</h1> 
            <p class="card-text">
                <a href="{{ url('/opd_ppfs') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
            </p>           
          </div>
        </div>
      </div>
      <h5 class="alert alert-primary text-primary" align="left">
        ข้อมูลผู้ป่วยใน ณ วันที่ <font style="color:red;">{{DateThai(date('Y-m-d'))}}</font> </font> เวลา: <font style="color:red;"><span id="realtime-clock_ipd"></span></font> 
        Admit ปัจจุบัน: <font style="color:red;">{{$admit_now}}</font> AN
      </h5>
      <div class="col-sm-3">
        <div class="card text-white bg-9 mb-3" style="max-width: 18rem;" >
          <div class="card-header">
            <ion-icon name="people-outline"></ion-icon>
            Admit Homeward : Authen
          </div>
          <div class="card-body">
            <h1 class="card-title text-center">{{$admit_homeward}} : {{$admit_homeward_endpoint}}</h1> 
            <p class="card-text">
                <a href="{{ url('/ipd_homeward') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
            </p>           
          </div>
        </div>
      </div>
      <div class="col-sm-3">
          <div class="card text-white bg-10 mb-3" style="max-width: 18rem;">
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              Chart รอแพทย์สรุป : รอบันทึก ICD10
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$non_diagtext}} : {{$non_icd10}}</h1>  
              <p class="card-text">
                  <a href="{{ url('/ipd_non_dchsummary') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>  
            </div>
          </div>
      </div>        
      <div class="col-sm-3">
          <div class="card text-white bg-11 mb-3" style="max-width: 18rem;">
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              รอโอนค่าใช้จ่าย
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$not_transfer}}</h1>
              <p class="card-text">
                  <a href="{{ url('/ipd_finance_chk_opd_wait_transfer') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>  
            </div>
          </div>
      </div>
      <div class="col-sm-3">
          <div class="card text-white bg-12 mb-3" style="max-width: 18rem;">
            <div class="card-header">
              <ion-icon name="people-outline"></ion-icon>
              รอชำระเงินสด : จำนวนเงิน
            </div>
            <div class="card-body">
              <h1 class="card-title text-center">{{$wait_paid_money}} : {{number_format($sum_wait_paid_money,2)}}</h1>  
              <p class="card-text">
                  <a href="{{ url('/ipd_finance_chk_wait_rcpt_money') }}" target="_blank" class="text-white" style="text-decoration: none;"> more detail...</a>
              </p>  
            </div>
          </div>
      </div>    
      <br>        
      <div id="bed_occupancy" style="width: 100%; height: 200px"><font color="#4154f1"><strong>อัตราครองเตียง ปีงบประมาณ {{$budget_year}}</strong></font></div>
      <div class="col-sm-12">        
      <br>
      <h6 class="text-success" align="left"><strong>ข้อมูลผู้ปวยในรวม Homeward</strong></h6>
        <div style="overflow-x:auto;">          
          <table class="table table-bordered table-striped">
              <thead>
              <tr class="table-success">
                  <th class="text-center">เดือน</th>
                  <th class="text-center">AN</th>
                  <th class="text-center">วันนอนรวม</th>
                  <th class="text-center">อัตราครองเตียง</th>
                  <th class="text-center">ActiveBase</th>
                  <th class="text-center">CMI</th>
                  <th class="text-center">RW</th>             
              </tr>
              </thead>        
              @foreach($ip_all as $row)
              <tr>
                  <td align="center">{{ $row->month }}</td>
                  <td align="right">{{ number_format($row->an) }}</td>
                  <td align="right">{{ number_format($row->admdate) }}</td>
                  <td align="right">{{ $row->bed_occupancy }}</td>
                  <td align="right">{{ $row->active_bed }}</td>
                  <td align="right">{{ $row->cmi }}</td>
                  <td align="right">{{ $row->adjrw }}</td>          
              </tr>            
              @endforeach   
          </table>
        </div> 
      </div> 
    </div><!-- //row -->
    <hr>
    <div class="row" align="center">  
      <div class="col-sm-6">
        <h6 class="text-danger" align="left"><strong>ข้อมูลผู้ปวยใน ไม่รวม Homeward</strong></h6>     
        <div style="overflow-x:auto;">          
          <table class="table table-bordered table-striped">
              <thead>
              <tr class="table-danger">
                  <th class="text-center">เดือน</th>
                  <th class="text-center">AN</th>
                  <th class="text-center">วันนอนรวม</th>
                  <th class="text-center">อัตราครองเตียง</th>
                  <th class="text-center">ActiveBase</th>
                  <th class="text-center">CMI</th>
                  <th class="text-center">RW</th>                 
              </tr>
              </thead>        
              @foreach($ip_normal as $row)
              <tr>
                  <td align="center">{{ $row->month }}</td>
                  <td align="right">{{ number_format($row->an) }}</td>
                  <td align="right">{{ number_format($row->admdate) }}</td>
                  <td align="right">{{ $row->bed_occupancy }}</td>
                  <td align="right">{{ $row->active_bed }}</td>
                  <td align="right">{{ $row->cmi }}</td>
                  <td align="right">{{ $row->adjrw }}</td>               
              </tr>            
              @endforeach              
          </table>
        </div> 
      </div>  
      <div class="col-sm-6">
        <h6 class="text-primary" align="left"><strong>ข้อมูลผู้ปวย Homeward</strong></h6>     
        <div style="overflow-x:auto;">          
          <table class="table table-bordered table-striped">
              <thead>
              <tr class="table-primary">
                  <th class="text-center">เดือน</th>
                  <th class="text-center">AN</th>
                  <th class="text-center">วันนอนรวม</th>
                  <th class="text-center">อัตราครองเตียง</th>
                  <th class="text-center">ActiveBase</th>
                  <th class="text-center">CMI</th>
                  <th class="text-center">RW</th>            
              </tr>
              </thead>        
              @foreach($ip_homeward as $row)
              <tr>
                  <td align="center">{{ $row->month }}</td>
                  <td align="right">{{ number_format($row->an) }}</td>
                  <td align="right">{{ number_format($row->admdate) }}</td>
                  <td align="right">{{ $row->bed_occupancy }}</td>
                  <td align="right">{{ $row->active_bed }}</td>
                  <td align="right">{{ $row->cmi }}</td>
                  <td align="right">{{ $row->adjrw }}</td>               
              </tr>            
              @endforeach              
          </table>
        </div> 
      </div>     
    </div> <!-- //row -->

</div>

<!-- Modal -->
<div class="modal fade" id="nhsoModal" tabindex="-1" aria-labelledby="nhsoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-header">
        <h5>เลือกวันที่เข้ารับบริการ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="nhsoForm">
        <div class="modal-body">         
          <input type="date" id="vstdate" name="vstdate" class="form-control"  value="{{ date('Y-m-d') }}" required>

          <div id="loadingSpinner" class="mt-4 d-none">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">กำลังดึงข้อมูลจาก สปสช....</p>
          </div>

          <div id="resultMessage" class="mt-3 d-none"></div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">ดึงข้อมูล</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ionicon -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

<script>
    // ฟังก์ชันแสดงเวลาปัจจุบัน
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const time = `${hours}:${minutes}:${seconds}`;
        document.getElementById('realtime-clock').textContent = time;
        document.getElementById('realtime-clock_ipd').textContent = time;
    }

    // อัปเดตทุกวินาที
    setInterval(updateClock, 1000);
    updateClock();

    // รีโหลดหน้าทุก 1 นาที (60000 ms)
    setTimeout(function() {
        location.reload();
    }, 60000);
</script>

@endsection

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("nhsoForm");
    const spinner = document.getElementById("loadingSpinner");
    const resultMessage = document.getElementById("resultMessage");
    const nhsoModal = document.getElementById('nhsoModal');

    form.addEventListener("submit", function (e) {
        e.preventDefault();
        spinner.classList.remove("d-none");
        resultMessage.classList.add("d-none");
        resultMessage.innerHTML = "";

        const formData = new FormData(form);

        fetch("{{ url('/nhso_endpoint_pull') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: formData
        })
        .then(response => {
            spinner.classList.add("d-none");
            if (!response.ok) throw new Error("โหลดล้มเหลว");
            return response.json();
        })
        .then(data => {
            resultMessage.classList.remove("d-none");
            resultMessage.classList.add("text-success");
            resultMessage.innerHTML = "✅ " + (data.message || "ดึงข้อมูลสำเร็จ");
        })
        .catch(err => {
            resultMessage.classList.remove("d-none");
            resultMessage.classList.add("text-danger");
            resultMessage.innerHTML = "❌ ดึงข้อมูลล้มเหลว";
        });
    });

    nhsoModal.addEventListener('hide.bs.modal', function () {
        // ✅ Redirect ไปหน้า /home เมื่อปิด Modal
        window.location.href = "{{ url('/home') }}";
    });
});
</script>

<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
      new ApexCharts(document.querySelector("#bed_occupancy"), {
          
          series: [{
              name: 'อัตราครองเตียง',
              data: <?php echo json_encode($bed_occupancy); ?>,
                  }],
        
          chart: {
              height: 200,
              type: 'area',
              toolbar: {
              show: false
              },
          },
          markers: {
              size: 4
          },
          colors: ['#4154f1'],
          fill: {
              type: "gradient",
              gradient: {
              shadeIntensity: 1,
              opacityFrom: 0.3,
              opacityTo: 0.4,
              stops: [0, 90, 100]
              }
          },
          dataLabels: {
              enabled: true
          },
          stroke: {
              curve: 'smooth',
              width: 2
          },
          xaxis: {
              type: 'text',
              categories:  <?php echo json_encode($month); ?>,
          }
          }).render();
      });
</script>
