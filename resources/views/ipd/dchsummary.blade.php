@extends('layouts.app')

@section('content')

    <!-- Page Header & Filter -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-building-check text-primary me-2"></i>
                IPD Discharge Summary
            </h5>
            <div class="text-muted small mt-1">ข้อมูลผู้ป่วยที่ Discharge วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</div>
        </div>
        
        <div class="d-flex align-items-center">
            <form method="POST" enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-calendar-event me-1"></i> {{ __('วันที่') }}</span>
                    
                    <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                    <input type="text" class="form-control datepicker_th" id="start_date_picker" value="{{ $start_date }}" readonly style="max-width: 140px; background-color: #fff;">
                    
                    <span class="input-group-text bg-white">{{ __('ถึง') }}</span>
                    
                    <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                    <input type="text" class="form-control datepicker_th" id="end_date_picker" value="{{ $end_date }}" readonly style="max-width: 140px; background-color: #fff;">
                    
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="bi bi-search me-1"></i> {{ __('ค้นหา') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stats Row 1 -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card dash-card border-0 h-100">
                <div class="card-body p-3">
                    <div class="text-primary mb-2"><i class="bi bi-people-fill fs-4"></i></div>
                    <div class="text-muted small">Discharge</div>
                    <h3 class="fw-bold text-dark mb-0">{{ $sum_discharge }}</h3>
                    <div class="text-muted" style="font-size: 0.7rem;">AN</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('ipd/wait_doctor_dchsummary') }}" target="_blank" class="text-decoration-none h-100">
                <div class="card dash-card border-0 h-100 border-start border-4 border-warning">
                    <div class="card-body p-3">
                        <div class="text-warning mb-2"><i class="bi bi-vector-pen fs-4"></i></div>
                        <div class="text-muted small">รอแพทย์สรุป</div>
                        <h3 class="fw-bold text-dark mb-0">{{ $sum_wait_dchsummary }}</h3>
                        <div class="text-warning" style="font-size: 0.7rem;">คลิกเพื่อดูรายละเอียด <i class="bi bi-chevron-right"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('ipd/wait_icd_coder') }}" class="text-decoration-none h-100">
                <div class="card dash-card border-0 h-100 border-start border-4 border-info">
                    <div class="card-body p-3">
                        <div class="text-info mb-2"><i class="bi bi-tag-fill fs-4"></i></div>
                        <div class="text-muted small">รอลงรหัส ICD10</div>
                        <h3 class="fw-bold text-dark mb-0">{{ $sum_wait_icd_coder }}</h3>
                        <div class="text-info" style="font-size: 0.7rem;">คลิกเพื่อดูรายละเอียด <i class="bi bi-chevron-right"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('ipd/dchsummary') }}" class="text-decoration-none h-100">
                <div class="card dash-card border-0 h-100 border-start border-4 border-success">
                    <div class="card-body p-3">
                        <div class="text-success mb-2"><i class="bi bi-check-circle-fill fs-4"></i></div>
                        <div class="text-muted small">สรุปแล้ว</div>
                        <h3 class="fw-bold text-dark mb-0">{{ $sum_dchsummary }}</h3>
                        <div class="text-success" style="font-size: 0.7rem;">คลิกเพื่อดูรายละเอียด <i class="bi bi-chevron-right"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('ipd/dchsummary_audit') }}" class="text-decoration-none h-100">
                <div class="card dash-card border-0 h-100 border-start border-4 border-danger">
                    <div class="card-body p-3">
                        <div class="text-danger mb-2"><i class="bi bi-clipboard-check-fill fs-4"></i></div>
                        <div class="text-muted small">Chart Audit</div>
                        <h3 class="fw-bold text-dark mb-0">{{ $sum_dchsummary_audit }}</h3>
                        <div class="text-danger" style="font-size: 0.7rem;">คลิกเพื่อดูรายละเอียด <i class="bi bi-chevron-right"></i></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card dash-card border-0 h-100 bg-primary-soft">
                <div class="card-body p-3">
                    <div class="text-primary mb-2"><i class="bi bi-calculator fs-4"></i></div>
                    <div class="text-muted small">SumAdjRW รวม</div>
                    <h3 class="fw-bold text-primary mb-0">{{ number_format($rw_all, 2) }}</h3>
                    <div class="text-muted" style="font-size: 0.7rem;">RW.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats Row 2 (SumAdjRW by Pttype) -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-2 offset-lg-1">
            <div class="card dash-card border-0 border-bottom border-4 border-success">
                <div class="card-body p-2">
                    <div class="text-muted small mb-1">UCS ในเขต</div>
                    <div class="fw-bold text-dark">{{ number_format($rw_ucs, 2) }} <span class="small text-muted">Rw.</span></div>
                    <div class="fw-bold text-success">{{ number_format($rw_receive_ucs, 2) }} <span class="small">฿</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card dash-card border-0 border-bottom border-4 border-success">
                <div class="card-body p-2">
                    <div class="text-muted small mb-1">UCS นอกเขต</div>
                    <div class="fw-bold text-dark">{{ number_format($rw_ucs2, 2) }} <span class="small text-muted">Rw.</span></div>
                    <div class="fw-bold text-success">{{ number_format($rw_receive_ucs2, 2) }} <span class="small">฿</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card dash-card border-0 border-bottom border-4 border-primary">
                <div class="card-body p-2">
                    <div class="text-muted small mb-1">SumAdjRW OFC</div>
                    <div class="fw-bold text-dark">{{ number_format($rw_ofc, 2) }} <span class="small text-muted">Rw.</span></div>
                    <div class="fw-bold text-primary">{{ number_format($rw_receive_ofc, 2) }} <span class="small">฿</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card dash-card border-0 border-bottom border-4 border-warning">
                <div class="card-body p-2">
                    <div class="text-muted small mb-1">SumAdjRW LGO</div>
                    <div class="fw-bold text-dark">{{ number_format($rw_lgo, 2) }} <span class="small text-muted">Rw.</span></div>
                    <div class="fw-bold text-warning-dark">{{ number_format($rw_receive_lgo, 2) }} <span class="small">฿</span></div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card dash-card border-0 border-bottom border-4 border-info">
                <div class="card-body p-2">
                    <div class="text-muted small mb-1">SumAdjRW SSS</div>
                    <div class="fw-bold text-dark">{{ number_format($rw_sss, 2) }} <span class="small text-muted">Rw.</span></div>
                    <div class="fw-bold text-info">{{ number_format($rw_receive_sss, 2) }} <span class="small">฿</span></div>
                </div>
            </div>
        </div>
    </div>
         <br>
    <!-- Patient List Card -->
    <div class="card dash-card border-top-0">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-list-columns-reverse text-primary me-2"></i>
                รายชื่อผู้ป่วย Discharge Summary
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="dchsummary" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" rowspan="2">#</th>           
                            <th class="text-center" rowspan="2">AN</th>
                            <th class="text-center" rowspan="2">ชื่อ-สกุล | สิทธิ</th>
                            <th class="text-center" colspan="3">การนอนโรงพยาบาล</th>
                            <th class="text-center" rowspan="2">AdjRW</th> 
                            <th class="text-center" rowspan="2">ICD10 Type 1-5</th>   
                            <th class="text-center" rowspan="2">แพทย์เจ้าของไข้</th>
                            <th class="text-center" colspan="2">Principle DX</th>
                            <th class="text-center" colspan="2">Comorbidity</th>
                            <th class="text-center" colspan="2">Complication</th>
                            <th class="text-center" colspan="2">Other DX</th>
                            <th class="text-center" colspan="2">Ext. Cause</th>
                        </tr> 
                        <tr>       
                            <th class="text-center small">Admit</th>                    
                            <th class="text-center small">DCH</th>   
                            <th class="text-center small">วันนอน</th>
                            
                            <th class="text-center small">วินิจฉัย</th>                    
                            <th class="text-center small">Audit</th>   
                            <th class="text-center small">วินิจฉัย</th>                    
                            <th class="text-center small">Audit</th>     
                            <th class="text-center small">วินิจฉัย</th>                    
                            <th class="text-center small">Audit</th>    
                            <th class="text-center small">วินิจฉัย</th>                    
                            <th class="text-center small">Audit</th>    
                            <th class="text-center small">วินิจฉัย</th>                    
                            <th class="text-center small">Audit</th>  
                        </tr>     
                    </thead> 
                    <tbody>
                        @php $count = 1 ; @endphp
                        @foreach($data as $row)          
                        <tr>
                            <td class="text-center text-muted small">{{ $count }}</td> 
                            <td class="text-start">
                                <div class="fw-bold text-primary">{{ $row->an }}</div>
                            </td>
                            <td class="text-start">
                                <div class="text-dark fw-bold">{{ $row->ptname }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</div>
                            </td>
                            <td class="text-center small">{{ DateThai($row->regdate) }}</td>
                            <td class="text-center small">{{ DateThai($row->dchdate) }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $row->admdate }}</span>
                            </td>
                            <td class="text-center fw-bold text-danger">{{ $row->adjrw }}</td>
                            <td class="text-start small">
                                <div class="d-flex gap-1 flex-wrap">
                                    <span class="badge bg-primary-soft text-primary">{{ $row->icd10_t1 }}</span>
                                    <span class="badge bg-secondary-soft text-secondary">{{ $row->icd10_t2 }}</span>
                                    <span class="badge bg-secondary-soft text-secondary">{{ $row->icd10_t3 }}</span>
                                    <span class="badge bg-secondary-soft text-secondary">{{ $row->icd10_t4 }}</span>
                                    <span class="badge bg-warning-soft text-warning">{{ $row->icd10_t5 }}</span>
                                </div>
                            </td>  
                            <td class="text-start small text-muted">{{ $row->owner_doctor_name }}</td>
                            
                            <td class="text-start small">
                                <div>{{ $row->dx1 }}</div>
                                <div class="text-primary" style="font-size: 0.65rem;">{{ $row->dx1_doctor }}</div>
                            </td>
                            <td class="text-start small bg-light-soft">
                                <div>{{ $row->dx1_audit }}</div>
                                <div class="text-success" style="font-size: 0.65rem;">{{ $row->dx1_doctor_audit }}</div>
                            </td>
                            
                            <td class="text-start small">
                                <div>{{ $row->dx2 }}</div>
                                <div class="text-primary" style="font-size: 0.65rem;">{{ $row->dx2_doctor }}</div>
                            </td>
                            <td class="text-start small bg-light-soft">
                                <div>{{ $row->dx2_audit }}</div>
                                <div class="text-success" style="font-size: 0.65rem;">{{ $row->dx2_doctor_audit }}</div>
                            </td>
                            
                            <td class="text-start small">
                                <div>{{ $row->dx3 }}</div>
                                <div class="text-primary" style="font-size: 0.65rem;">{{ $row->dx3_doctor }}</div>
                            </td>
                            <td class="text-start small bg-light-soft">
                                <div>{{ $row->dx3_audit }}</div>
                                <div class="text-success" style="font-size: 0.65rem;">{{ $row->dx3_doctor_audit }}</div>
                            </td>
                            
                            <td class="text-start small">
                                <div>{{ $row->dx4 }}</div>
                                <div class="text-primary" style="font-size: 0.65rem;">{{ $row->dx4_doctor }}</div>
                            </td>
                            <td class="text-start small bg-light-soft">
                                <div>{{ $row->dx4_audit }}</div>
                                <div class="text-success" style="font-size: 0.65rem;">{{ $row->dx4_doctor_audit }}</div>
                            </td>
                            
                            <td class="text-start small">
                                <div>{{ $row->dx5 }}</div>
                                <div class="text-primary" style="font-size: 0.65rem;">{{ $row->dx5_doctor }}</div>
                            </td>
                            <td class="text-start small bg-light-soft">
                                <div>{{ $row->dx5_audit }}</div>
                                <div class="text-success" style="font-size: 0.65rem;">{{ $row->dx5_doctor_audit }}</div>
                            </td>
                        </tr>                
                        @php $count++; @endphp
                        @endforeach  
                    </tbody>
                </table>
            </div>  
        </div>
    </div>
@endsection

@push('scripts')
  <script>
    $(document).ready(function () {
      
      // Initialize DataTable
      $('#dchsummary').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + 
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + 
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + 
                '<"col-md-6"p>' + 
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
              className: 'btn btn-success btn-sm',
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

      // Initialize Thai Date Picker
      $('.datepicker_th').datepicker({
          format: 'yyyy-mm-dd',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true
      });

      // Sync Start Date
      $('#start_date_picker').on('changeDate', function(e) {
          var date = e.date;
          if(date) {
              var day = ("0" + date.getDate()).slice(-2);
              var month = ("0" + (date.getMonth() + 1)).slice(-2);
              var year = date.getFullYear();
              $('#start_date').val(year + "-" + month + "-" + day);
          } else {
              $('#start_date').val('');
          }
      });

      // Sync End Date
      $('#end_date_picker').on('changeDate', function(e) {
          var date = e.date;
          if(date) {
              var day = ("0" + date.getDate()).slice(-2);
              var month = ("0" + (date.getMonth() + 1)).slice(-2);
              var year = date.getFullYear();
              $('#end_date').val(year + "-" + month + "-" + day);
          } else {
              $('#end_date').val('');
          }
      });

    });
  </script>
@endpush
