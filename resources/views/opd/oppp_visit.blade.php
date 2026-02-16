@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Filter -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-people-fill text-primary me-2"></i>
                สถิติผู้มารับบริการผู้ป่วยนอก
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณ {{ $budget_year }}</div>
        </div>
        
        <div class="d-flex align-items-center">
            <form method="POST" enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-check"></i></span>
                    <select class="form-select border-start-0" name="budget_year" style="min-width: 150px;">
                        @foreach ($budget_year_select as $row)
                            <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="bi bi-search me-1"></i> {{ __('ค้นหา') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card dash-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-bar-chart-line text-info me-2"></i>
                        จำนวนผู้มารับบริการรายเดือน
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="visit" style="width: 100%; height: 350px"></canvas>             
                </div>      
            </div>      
        </div>   
        <div class="col-xl-6">
            <div class="card dash-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-intersect text-warning me-2"></i>
                        จำนวนผู้มารับบริการแยก OP-PP
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="visit_oppp" style="width: 100%; height: 350px"></canvas>             
                </div>      
            </div>      
        </div>      
    </div>

    <!-- Stats Table 1: Pttype -->
    <div class="card dash-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-grid-3x3-gap text-success me-2"></i>
                จำนวนผู้มารับบริการแยกกลุ่มสิทธิหลัก (ครั้ง)
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="visit_pttype" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" rowspan="2">เดือน</th>
                            <th class="text-center" colspan="2" style="background-color: #f0fdf4; border-bottom-color: #bbf7d0 !important;">ทั้งหมด</th>
                            <th class="text-center" colspan="2" style="background-color: #eff6ff; border-bottom-color: #bfdbfe !important;">ประกันสุขภาพ ใน CUP</th> 
                            <th class="text-center" colspan="2" style="background-color: #eff6ff; border-bottom-color: #bfdbfe !important;">ประกันสุขภาพ นอก CUP</th>     
                            <th class="text-center" colspan="2" style="background-color: #fdf2f8; border-bottom-color: #fbcfe8 !important;">ข้าราชการ</th>  
                            <th class="text-center" colspan="2" style="background-color: #f5f3ff; border-bottom-color: #ddd6fe !important;">ประกันสังคม</th>
                            <th class="text-center" colspan="2" style="background-color: #fffbeb; border-bottom-color: #fef3c7 !important;">อปท.</th>
                            <th class="text-center" colspan="2" style="background-color: #ecfdf5; border-bottom-color: #d1fae5 !important;">ต่างด้าว</th>
                            <th class="text-center" colspan="2" style="background-color: #f8fafc; border-bottom-color: #e2e8f0 !important;">Stateless</th>
                            <th class="text-center" colspan="2" style="background-color: #fff7ed; border-bottom-color: #ffedd5 !important;">ชำระเงิน/พรบ.</th>                 
                        </tr>    
                        <tr>            
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th> 
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>    
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th> 
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>
                            <th class="text-center small">Visit</th>
                            <th class="text-center small">Income</th>                 
                        </tr>    
                    </thead> 
                    <tbody>
                        @php 
                            $sum_visit = 0 ; $sum_income = 0 ;   
                            $sum_ucs_incup = 0 ; $sum_ucs_incup_income = 0 ;
                            $sum_ucs_outcup = 0 ; $sum_ucs_outcup_income = 0 ;
                            $sum_ofc = 0 ; $sum_ofc_income = 0 ;
                            $sum_sss = 0 ; $sum_sss_income = 0 ;  
                            $sum_lgo = 0 ; $sum_lgo_income = 0 ;  
                            $sum_fss = 0 ; $sum_fss_income = 0 ;  
                            $sum_stp = 0 ; $sum_stp_income = 0 ;   
                            $sum_pay = 0 ; $sum_pay_income = 0 ;  
                        @endphp
                        @foreach($visit_month as $row)          
                        <tr>
                            <td class="text-center fw-bold">{{ $row->month }}</td> 
                            <td class="text-end">{{ number_format($row->visit) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->income,2) }}</td> 
                            <td class="text-end small">{{ number_format($row->ucs_incup) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->ucs_incup_income,2) }}</td> 
                            <td class="text-end small">{{ number_format($row->ucs_outcup) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->ucs_outcup_income,2) }}</td>
                            <td class="text-end small">{{ number_format($row->ofc) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->ofc_income,2) }}</td> 
                            <td class="text-end small">{{ number_format($row->sss) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->sss_income,2) }}</td> 
                            <td class="text-end small">{{ number_format($row->lgo) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->lgo_income,2) }}</td> 
                            <td class="text-end small">{{ number_format($row->fss) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->fss_income,2) }}</td>
                            <td class="text-end small">{{ number_format($row->stp) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->stp_income,2) }}</td> 
                            <td class="text-end small">{{ number_format($row->pay) }}</td>   
                            <td class="text-end small text-success">{{ number_format($row->pay_income,2) }}</td>              
                        </tr>                
                        @php 
                            $sum_visit += $row->visit ; $sum_income += $row->income ;
                            $sum_ucs_incup += $row->ucs_incup ; $sum_ucs_incup_income += $row->ucs_incup_income ;
                            $sum_ucs_outcup += $row->ucs_outcup ; $sum_ucs_outcup_income += $row->ucs_outcup_income ;
                            $sum_ofc += $row->ofc ; $sum_ofc_income += $row->ofc_income ;
                            $sum_sss += $row->sss ; $sum_sss_income += $row->sss_income ;
                            $sum_lgo += $row->lgo ; $sum_lgo_income += $row->lgo_income ;
                            $sum_fss += $row->fss ; $sum_fss_income += $row->fss_income ;
                            $sum_stp += $row->stp ; $sum_stp_income += $row->stp_income ;
                            $sum_pay += $row->pay ; $sum_pay_income += $row->pay_income ;
                        @endphp
                        @endforeach     
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="text-center">รวม</td>
                            <td class="text-end">{{number_format($sum_visit)}}</td>
                            <td class="text-end text-success">{{number_format($sum_income,2)}}</td>
                            <td class="text-end small">{{number_format($sum_ucs_incup)}}</td>     
                            <td class="text-end small text-success">{{number_format($sum_ucs_incup_income,2)}}</td>   
                            <td class="text-end small">{{number_format($sum_ucs_outcup)}}</td>     
                            <td class="text-end small text-success">{{number_format($sum_ucs_outcup_income,2)}}</td>    
                            <td class="text-end small">{{number_format($sum_ofc)}}</td>  
                            <td class="text-end small text-success">{{number_format($sum_ofc_income,2)}}</td> 
                            <td class="text-end small">{{number_format($sum_sss)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_sss_income,2)}}</td>
                            <td class="text-end small">{{number_format($sum_lgo)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_lgo_income,2)}}</td>
                            <td class="text-end small">{{number_format($sum_fss)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_fss_income,2)}}</td>
                            <td class="text-end small">{{number_format($sum_stp)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_stp_income,2)}}</td>
                            <td class="text-end small">{{number_format($sum_pay)}}</td>   
                            <td class="text-end small text-success">{{number_format($sum_pay_income,2)}}</td>                
                        </tr>   
                    </tfoot>
                </table>  
            </div>         
        </div>
    </div>

    <!-- Stats Table 2: Income -->
    <div class="card dash-card mb-4">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-cash-stack text-primary me-2"></i>
                จำนวนผู้มารับบริการแยกหมวดค่าใช้จ่าย (ยา/Lab)
            </h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="visit_income" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" rowspan="2">เดือน</th>
                            <th class="text-center" colspan="2" style="background-color: #f0fdf4; border-bottom-color: #bbf7d0 !important;">ทั้งหมด</th>
                            <th class="text-center" colspan="2" style="background-color: #eff6ff; border-bottom-color: #bfdbfe !important;">ประกันสุขภาพ</th>     
                            <th class="text-center" colspan="2" style="background-color: #fdf2f8; border-bottom-color: #fbcfe8 !important;">ข้าราชการ</th>  
                            <th class="text-center" colspan="2" style="background-color: #f5f3ff; border-bottom-color: #ddd6fe !important;">ประกันสังคม</th>
                            <th class="text-center" colspan="2" style="background-color: #fffbeb; border-bottom-color: #fef3c7 !important;">อปท.</th>
                            <th class="text-center" colspan="2" style="background-color: #ecfdf5; border-bottom-color: #d1fae5 !important;">ต่างด้าว</th>
                            <th class="text-center" colspan="2" style="background-color: #f8fafc; border-bottom-color: #e2e8f0 !important;">Stateless</th>
                            <th class="text-center" colspan="2" style="background-color: #fff7ed; border-bottom-color: #ffedd5 !important;">ชำระเงิน/พรบ.</th>                 
                        </tr>    
                        <tr>            
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>    
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th> 
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>
                            <th class="text-center small">Drug</th>
                            <th class="text-center small">Lab</th>               
                        </tr>    
                    </thead> 
                    <tbody>
                        @php 
                            $sum_inc_drug = 0 ; $sum_inc_lab = 0 ;   
                            $sum_ucs_inc_drug = 0 ; $sum_ucs_inc_lab = 0 ; 
                            $sum_ofc_inc_drug = 0 ; $sum_ofc_inc_lab = 0 ;
                            $sum_sss_inc_drug = 0 ; $sum_sss_inc_lab = 0 ;  
                            $sum_lgo_inc_drug = 0 ; $sum_lgo_inc_lab = 0 ;  
                            $sum_fss_inc_drug = 0 ; $sum_fss_inc_lab = 0 ;  
                            $sum_stp_inc_drug = 0 ; $sum_stp_inc_lab = 0 ;   
                            $sum_pay_inc_drug = 0 ; $sum_pay_inc_lab = 0 ;  
                        @endphp
                        @foreach($visit_month as $row)          
                        <tr>
                            <td class="text-center fw-bold">{{ $row->month }}</td> 
                            <td class="text-end text-primary">{{ number_format($row->inc_drug) }}</td>
                            <td class="text-end text-success">{{ number_format($row->inc_lab,2) }}</td> 
                            <td class="text-end small text-primary">{{ number_format($row->ucs_inc_drug) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->ucs_inc_lab,2) }}</td> 
                            <td class="text-end small text-primary">{{ number_format($row->ofc_inc_drug) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->ofc_inc_lab,2) }}</td> 
                            <td class="text-end small text-primary">{{ number_format($row->sss_inc_drug) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->sss_inc_lab,2) }}</td> 
                            <td class="text-end small text-primary">{{ number_format($row->lgo_inc_drug) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->lgo_inc_lab,2) }}</td> 
                            <td class="text-end small text-primary">{{ number_format($row->fss_inc_drug) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->fss_inc_lab,2) }}</td>
                            <td class="text-end small text-primary">{{ number_format($row->stp_inc_drug) }}</td> 
                            <td class="text-end small text-success">{{ number_format($row->stp_inc_lab,2) }}</td> 
                            <td class="text-end small text-primary">{{ number_format($row->pay_inc_drug) }}</td>   
                            <td class="text-end small text-success">{{ number_format($row->pay_inc_lab,2) }}</td>              
                        </tr>                
                        @php 
                            $sum_inc_drug += $row->inc_drug ; $sum_inc_lab += $row->inc_lab ;
                            $sum_ucs_inc_drug += $row->ucs_inc_drug ; $sum_ucs_inc_lab += $row->ucs_inc_lab ;
                            $sum_ofc_inc_drug += $row->ofc_inc_drug ; $sum_ofc_inc_lab += $row->ofc_inc_lab ;
                            $sum_sss_inc_drug += $row->sss_inc_drug ; $sum_sss_inc_lab += $row->sss_inc_lab ;
                            $sum_lgo_inc_drug += $row->lgo_inc_drug ; $sum_lgo_inc_lab += $row->lgo_inc_lab ;
                            $sum_fss_inc_drug += $row->fss_inc_drug ; $sum_fss_inc_lab += $row->fss_inc_lab ;
                            $sum_stp_inc_drug += $row->stp_inc_drug ; $sum_stp_inc_lab += $row->stp_inc_lab ;
                            $sum_pay_inc_drug += $row->pay_inc_drug ; $sum_pay_inc_lab += $row->pay_inc_lab ;
                        @endphp
                        @endforeach     
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="text-center">รวม</td>
                            <td class="text-end text-primary">{{number_format($sum_inc_drug,2)}}</td>
                            <td class="text-end text-success">{{number_format($sum_inc_lab,2)}}</td>
                            <td class="text-end small text-primary">{{number_format($sum_ucs_inc_drug,2)}}</td>     
                            <td class="text-end small text-success">{{number_format($sum_ucs_inc_lab,2)}}</td>   
                            <td class="text-end small text-primary">{{number_format($sum_ofc_inc_drug,2)}}</td>  
                            <td class="text-end small text-success">{{number_format($sum_ofc_inc_lab,2)}}</td> 
                            <td class="text-end small text-primary">{{number_format($sum_sss_inc_drug,2)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_sss_inc_lab,2)}}</td>
                            <td class="text-end small text-primary">{{number_format($sum_lgo_inc_drug,2)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_lgo_inc_lab,2)}}</td>
                            <td class="text-end small text-primary">{{number_format($sum_fss_inc_drug,2)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_fss_inc_lab,2)}}</td>
                            <td class="text-end small text-primary">{{number_format($sum_stp_inc_drug,2)}}</td>
                            <td class="text-end small text-success">{{number_format($sum_stp_inc_lab,2)}}</td>
                            <td class="text-end small text-primary">{{number_format($sum_pay_inc_drug,2)}}</td>   
                            <td class="text-end small text-success">{{number_format($sum_pay_inc_lab,2)}}</td>                
                        </tr>   
                    </tfoot>
                </table>  
            </div>         
        </div>
    </div>
</div>
<br>

@endsection

@push('scripts')
  <!-- Vendor JS Files inside push to ensure correct order if needed -->
  <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

  <script>
    $(document).ready(function () {
      const commonConfig = {
        dom: '<"d-flex justify-content-end align-items-center gap-2 mb-3"fB>' + 'rt',
        ordering: false,
        paging: false,
        info: false,
        lengthChange: false,
        language: { search: "ค้นหา:" }
      };

      $('#visit_pttype').DataTable({
        ...commonConfig,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
            className: 'btn btn-success btn-sm',
            title: 'จำนวนผู้มารับบริการผู้ป่วยนอกแยกกลุ่มสิทธิหลัก (ครั้ง) ปีงบประมาณ {{$budget_year}}'
        }]
      });

      $('#visit_income').DataTable({
        ...commonConfig,
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
            className: 'btn btn-success btn-sm',
            title: 'จำนวนผู้มารับบริการผู้ป่วยนอกแยกหมวดค่าใช้จ่าย ปีงบประมาณ {{$budget_year}}'
        }]
      });
    });

    document.addEventListener("DOMContentLoaded", () => {
        // Bar Chart Config
        function createBarChart(selector, labels, datasets) {
            new Chart(document.querySelector(selector), {
                type: 'bar',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            formatter: (value) => value > 0 ? Number(value).toLocaleString() : '',
                            font: { weight: 'bold', size: 10 }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => v.toLocaleString() } }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        createBarChart('#visit', @json($month), [
            {
                label: 'ครั้ง (Visits)',
                data: @json($visit),
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 4
            },
            {
                label: 'คน (Patients)',
                data: @json($hn),
                backgroundColor: 'rgba(139, 92, 246, 0.6)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 1,
                borderRadius: 4
            }
        ]);

        createBarChart('#visit_oppp', @json($month), [
            {
                label: 'OP (General)',
                data: @json($visit_op),
                backgroundColor: 'rgba(249, 115, 22, 0.6)',
                borderColor: 'rgb(249, 115, 22)',
                borderWidth: 1,
                borderRadius: 4
            },
            {
                label: 'PP (Health Promotion)',
                data: @json($visit_pp),
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1,
                borderRadius: 4
            }
        ]);
    });
  </script>
@endpush

