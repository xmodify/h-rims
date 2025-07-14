@extends('layouts.app')

@section('content')

<div class="container-fluid"> 
    <div class="row"  >
        <form method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row" >
                    <label class="col-md-3 col-form-label text-md-end my-1">{{ __('วันที่') }}</label>
                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control my-1" placeholder="Date" value="{{ $start_date }}" >
                </div>
                    <label class="col-md-1 col-form-label text-md-end my-1">{{ __('ถึง') }}</label>
                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control my-1" placeholder="Date" value="{{ $end_date }}" >
                </div>
                <div class="col-md-1" >
                    <button type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
                </div>
            </div>
        </form> 
    </div><!-- row --> 

    <div class="row"  >
        <div class="col-sm-12"> 
            <div class="alert alert-success" role="alert"><strong>ข้อมูลผู้ป่วยที่ Discharge วันที่ {{DateThai($start_date)}} ถึง {{DateThai($end_date)}}</strong></div>          
            <div class="row" align="center">
                <div class="col-sm-2">
                    <div class="card text-white mb-3" style="max-width: 18rem; background-color: #0d6efd" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            Discharge
                        </div>
                        <div class="card-body">
                            <h1 class="card-title text-center">{{$sum_discharge}}</h1> 
                            <p class="card-text">
                                AN
                            </p>             
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card text-white mb-3" style="max-width: 18rem; background-color: #5677fc" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            Chart รอแพทย์สรุป
                        </div>
                        <div class="card-body">
                            <h1 class="card-title text-center">{{$sum_wait_dchsummary}}</h1>
                            <p class="card-text">
                                <a href="{{ url('ipd/wait_doctor_dchsummary') }}" target="_blank" class="text-white" style="text-decoration: none; "> more detail...</a>
                            </p>            
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card text-white mb-3" style="max-width: 18rem; background-color: #0dcaf0" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            Chart รอลงรหัสโรค ICD10 
                        </div>
                        <div class="card-body">
                            <h1 class="card-title text-center">{{$sum_wait_icd_coder}}</h1> 
                            <p class="card-text">
                                <a href="{{ url('ipd/wait_icd_coder') }}" class="text-white" style="text-decoration: none; "> more detail...</a>
                            </p>              
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card text-white mb-3" style="max-width: 18rem; background-color: #20c997" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            Chart สรุปแล้ว
                        </div>
                        <div class="card-body">
                            <h1 class="card-title text-center">{{$sum_dchsummary}}</h1>   
                            <p class="card-text">
                                <a href="{{ url('ipd/dchsummary') }}" class="text-white" style="text-decoration: none; "> more detail...</a>
                            </p>               
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card text-white mb-3" style="max-width: 18rem; background-color: #fd7e14" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            Chart Audit 
                        </div>
                        <div class="card-body">
                            <h1 class="card-title text-center">{{$sum_dchsummary_audit}}</h1>  
                            <p class="card-text">
                                <a href="{{ url('ipd/dchsummary_audit') }}" class="text-white" style="text-decoration: none; "> more detail...</a>
                            </p>               
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card text-white mb-3" style="max-width: 18rem; background-color: #ffc107" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            AdjRW รวม
                        </div>
                        <div class="card-body">
                            <h1 class="card-title text-center">{{number_format($rw_all,2)}} </h1>
                            <p class="card-text">
                                Rw.
                            </p>   
                        </div>
                    </div>
                </div>
            </div>
                <!-- Row-->
            <div class="row" align="center">
                <div class="col-sm-2">
                    <div class="card border border-success text-primary" style="max-width: 18rem; background-color:#e1f5fe" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            AdjRW UCS ในเขต
                        </div>
                        <div class="card-body">
                            <h4 class="card-title text-center">{{number_format($rw_ucs,2)}} Rw.</h4>
                            <h4 class="card-title text-center"><font style="color: green;" >{{number_format($rw_receive_ucs,2)}} บาท</font></h4>        
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card border border-success text-primary" style="max-width: 18rem; background-color:#e1f5fe" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            AdjRW UCS นอกเขต
                        </div>
                        <div class="card-body">
                            <h4 class="card-title text-center">{{number_format($rw_ucs2,2)}} Rw.</h4>
                            <h4 class="card-title text-center"><font style="color: green;" >{{number_format($rw_receive_ucs2,2)}} บาท</font></h4>        
                        </div>
                    </div>
                </div>                
                <div class="col-sm-2">
                    <div class="card border border-success text-primary" style="max-width: 18rem; background-color:#e1f5fe" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            AdjRW OFC
                        </div>
                        <div class="card-body">
                            <h4 class="card-title text-center">{{number_format($rw_ofc,2)}} Rw.</h4>
                            <h4 class="card-title text-center"><font style="color: green;" >{{number_format($rw_receive_ofc,2)}} บาท</font></h4>        
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card border border-success text-primary" style="max-width: 18rem; background-color:#e1f5fe" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            AdjRW LGO
                        </div>
                        <div class="card-body">
                            <h4 class="card-title text-center">{{number_format($rw_lgo,2)}} Rw.</h4>
                            <h4 class="card-title text-center"><font style="color: green;" >{{number_format($rw_receive_lgo,2)}} บาท</font></h4>        
                        </div>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="card border border-success text-primary" style="max-width: 18rem; background-color:#e1f5fe" >
                        <div class="card-header">
                            <ion-icon name="people-outline"></ion-icon>
                            AdjRW SSS
                        </div>
                        <div class="card-body">
                            <h4 class="card-title text-center">{{number_format($rw_sss,2)}} Rw.</h4>
                            <h4 class="card-title text-center"><font style="color: green;" >{{number_format($rw_receive_sss,2)}} บาท</font></h4>        
                        </div>
                    </div>
                </div>                
            </div >
         <br>
            <div style="overflow-x:auto;">    
                <table id="dchsummary" class="table table-bordered table-striped my-3">
                    <thead>
                    <tr class="table-primary">
                        <th class="text-center" rowspan="2"><p align="center">ลำดับ</p></th>           
                        <th class="text-center" rowspan="2"><p align="center">AN</p></th>
                        <th class="text-center" rowspan="2"><p align="center">วันที่ Admit</p></th>
                        <th class="text-center" rowspan="2"><p align="center" width = "5%">ชื่อ-สกุล</p></th>
                        <th class="text-center" rowspan="2"><p align="center" width = "5%">สิทธิการรักษา</p></th>
                        <th class="text-center" rowspan="2"><p align="center">วันที่ Discharge</p></th>
                        <th class="text-center" rowspan="2"><p align="center">วันนอน</p></th>                         
                        <th class="text-center" rowspan="2"><p align="center">AdjRW</p></th> 
                        <th class="text-center" rowspan="2" style="background-color: #d0d9ff" width = "9%"><p align="center">ICD10 Type 1|2|3|4|5</p></th>   
                        <th class="text-center" rowspan="2" style="background-color: #b3e5fc" width = "10%"><p align="center">แพทย์เจ้าของไข้</p></th>
                        <th class="text-center" colspan="2" style="background-color: #b3e5fc"><p align="center">Principle Diagnosis</p></th>
                        <th class="text-center" colspan="2" style="background-color: #b3e5fc"><p align="center">Comorbidity</p></th>
                        <th class="text-center" colspan="2" style="background-color: #b3e5fc"><p align="center">Complication</p></th>
                        <th class="text-center" colspan="2" style="background-color: #b3e5fc"><p align="center">Other Diagnosis</p></th>
                        <th class="text-center" colspan="2" style="background-color: #b3e5fc"><p align="center">External Cause</p></th>  
                    </tr> 
                    <tr class="table-primary">       
                        <th class="text-center" style="background-color: #b3e5fc">วินิจฉัย</th>                    
                        <th class="text-center" style="background-color: #b3e5fc">Audit</th>   
                        <th class="text-center" style="background-color: #b3e5fc">วินิจฉัย</th>                    
                        <th class="text-center" style="background-color: #b3e5fc">Audit</th>     
                        <th class="text-center" style="background-color: #b3e5fc">วินิจฉัย</th>                    
                        <th class="text-center" style="background-color: #b3e5fc">Audit</th>    
                        <th class="text-center" style="background-color: #b3e5fc">วินิจฉัย</th>                    
                        <th class="text-center" style="background-color: #b3e5fc">Audit</th>    
                        <th class="text-center" style="background-color: #b3e5fc">วินิจฉัย</th>                    
                        <th class="text-center" style="background-color: #b3e5fc">Audit</th>  
                    </tr>     
                    </thead> 
                    <?php $count = 1 ; ?> 
                    @foreach($data as $row)          
                    <tr>
                        <td align="center">{{ $count }}</td> 
                        <td align="left">{{ $row->an }}</td>
                        <td align="center">{{ DateThai($row->regdate) }}</td>
                        <td align="left" width = "5%">{{ $row->ptname }}</td>
                        <td align="left" width = "5%">{{ $row->pttype }}</td>
                        <td align="center">{{ DateThai($row->dchdate) }}</td>
                        <td align="center">{{ $row->admdate }}</td>
                        <td align="left"><font color="#b0120a"> {{ $row->rw }}</font> </td>
                        <td align="left" width = "9%"><font color="#0d6efd">{{ $row->icd10_t1 }}</font>|<font color="#6610f2">{{ $row->icd10_t2 }}</font>|
                            <font color="#6610f2">{{ $row->icd10_t3 }}</font>|<font color="#6610f2">{{ $row->icd10_t4 }}</font>|<font color="#fd7e14">{{ $row->icd10_t5}}</font></td>  
                        <td align="left" width = "10%">{{ $row->owner_doctor_name }}</td>
                        <td align="left">{{ $row->dx1 }} <font color="#0d6efd">{{ $row->dx1_doctor }}</font></td>
                        <td align="left">{{ $row->dx1_audit }} <font color="#259b24">{{ $row->dx1_doctor_audit }}</font></td>
                        <td align="left">{{ $row->dx2 }} <font color="#0d6efd">{{ $row->dx2_doctor }}</font></td>
                        <td align="left">{{ $row->dx2_audit }} <font color="#259b24">{{ $row->dx2_doctor_audit }}</font></td>
                        <td align="left">{{ $row->dx3 }} <font color="#0d6efd">{{ $row->dx3_doctor }}</font></td>
                        <td align="left">{{ $row->dx3_audit }} <font color="#259b24">{{ $row->dx3_doctor_audit }}</font></td>
                        <td align="left">{{ $row->dx4 }} <font color="#0d6efd">{{ $row->dx4_doctor }}</font></td>
                        <td align="left">{{ $row->dx4_audit }} <font color="#259b24">{{ $row->dx4_doctor_audit }}</font></td>
                        <td align="left">{{ $row->dx5 }} <font color="#0d6efd">{{ $row->dx5_doctor }}</font></td>
                        <td align="left">{{ $row->dx5_audit }} <font color="#259b24">{{ $row->dx5_doctor_audit }}</font></td>  
                    </tr>                
                    <?php $count++; ?>
                    @endforeach  
                </table>
            </div>  
        </div>
    </div>
</div>

@endsection

<!-- ionicon -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>


@push('scripts')
  <script>
    $(document).ready(function () {
      $('#dchsummary').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + // Show รายการ
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + // Info
                '<"col-md-6"p>' + // Pagination
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel',
              className: 'btn btn-success',
              title: 'ข้อมูลผู้ป่วยที่ Discharge วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: {
              previous: "ก่อนหน้า",
              next: "ถัดไป"
            }
        }
      });
    });
  </script>
@endpush
