<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title >ตรวจสอบค่ารักษาพยาบาลผู้ป่วยใน</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <!-- <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

<style>
  table {
  border-collapse: collapse;
  border-spacing: 0;
  width: 100%;
  border: 1px solid #ddd;
  }
  th, td {
  padding: 8px;
  }  
</style>
</head>
<body>
<!-- row -->
<div class="container">
  <div class="card">
    <div class="card-body"> 
      <div class="row">        
        <div class="col-md-12"> 
          <div style="overflow-x:auto;">            
            <table class="table table-striped table-bordered">
              <thead>
                <tr class="table-success">
                  <th class="text-center" colspan="13">ตรวจสอบค่ารักษาพยาบาลผู้ป่วยใน</th>    
                </tr>
                <tr class="table-primary">
                    <th class="text-center">ลำดับ</th>           
                    <th class="text-center">Ward</th>  
                    <th class="text-center">เตียง</th>
                    <th class="text-center">AN</th>
                    <th class="text-center">สิทธิการรักษา</th>   
                    <th class="text-center">โอนค่ารักษา</th> 
                    <th class="text-center">รอโอนจาก OPD</th>    
                    <th class="text-center">ค่ารักษาทั้งหมด</th>  
                    <th class="text-center">ลูกหนี้</th> 
                    <th class="text-center">ต้องชำระ</th>    
                    <th class="text-center">ชำระแล้ว</th> 
                    <th class="text-center">รอชำระ</th>     
                </tr>
              </thead> 
              <tbody>  
                <?php $count = 1 ; ?>
                @foreach($finance_chk as $row) 
                <tr>
                  <td align="center">{{ $count }}</td>
                  <td align="left">{{$row->ward}}</td>             
                  <td align="rigth">{{$row->bedno}}</td>
                  <td align="center">{{$row->an}}</td> 
                  <td align="left">{{$row->pttype}} [{{$row->hospmain}}]</td> 
                  <td align="center">{{$row->finance_transfer}}</td> 
                  <td align="right">{{ number_format($row->opd_wait_money,2) }}</td>
                  <td align="right">{{ number_format($row->item_money,2) }}</td>
                  <td align="right">{{ number_format($row->wait_debt_money,2) }}</td>
                  <td align="right">{{ number_format($row->paid_money,2) }}</td>
                  <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                  <td align="right">{{ number_format($row->wait_paid_money,2) }}</td>
                </tr>
                <?php $count++; ?>
                @endforeach                 
              </tbody>
            </table>   
          </div>          
        </div>  
      </div>             
    </div>      
  </div>          
</div>      
</body>
</html>