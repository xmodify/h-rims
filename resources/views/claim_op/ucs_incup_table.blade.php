    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <!-- Section 1: Chart -->
        <div class="px-4 pt-2 pb-0 border-bottom">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                สถิติการเรียกเก็บและชดเชยรายเดือน ปีงบประมาณ {{ $budget_year }}
            </h6>
            <div style="height: 300px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Tabs & Tables -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ UC-OP ใน CUP
                    </h6>
                    <span class="text-muted small">
                        วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
                    </span>
                </div>
                
                <div class="filter-group">
                    <form id="form_indiv" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                        @csrf            
                        <span class="fw-bold text-muted small text-nowrap me-2">เลือกวันที่รับบริการ</span>
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="budget_year" value="{{ $budget_year }}">
                            
                            
                            <!-- Start Date -->
                            <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                            <input type="text" id="start_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>

                            <!-- End Date -->
                            <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                            <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">

                            <button type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                            <button onclick="checkFdhBulk(event)" type="button" class="btn btn-info text-white px-3 shadow-sm" title="ดึงสถานะ FDH ตามช่วงเวลาที่เลือก (ทีละ 1 วัน)">
                                <i class="bi bi-arrow-repeat me-1"></i> ดึง FDH
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> รอส่ง Claim
                    </button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab">
                        <i class="bi bi-send-check me-1"></i> ส่ง Claim แล้ว
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Waiting for Claim -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th> 
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">เบิก/ส่ง</th>
                                    <th class="text-center">วัน-เวลา | Q</th>     
                                    <th class="text-center">HN</th>    
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center">รายการต้องเรียกเก็บ</th>  
                                    <th class="text-center">ค่ารักษา</th> 
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center text-primary">เรียกเก็บ</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_claim_price = 0; 
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            {{-- แดง: ข้อมูลไม่ครบ (priority สูงสุด) --}}
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                            {{-- เหลือง: มี warnings (ins_ucs) --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif($row->endpoint_valid)
                                            {{-- เขียว: ข้อมูลครบ + ปิดสิทธิแล้ว --}}
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            {{-- เหลือง: ข้อมูลครบ แต่ยังไม่ปิดสิทธิ --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->confirm_and_locked == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">ประสงค์เบิก:</span>
                                                @if($row->request_funds == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">พร้อมส่ง:</span>
                                                @if($row->confirm_and_locked == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="พร้อมส่ง Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ยังไม่พร้อมส่ง N"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 

                                    <td class="text-start small text-muted" style="font-size:0.7rem; max-width:180px;">{{ $row->claim_list }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td> 
                                  </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_claim_price += $row->claim_price; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="7" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div>  
                <!-- Tab 2: Claims Sent -->
                <div class="tab-pane fade" id="claim" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" rowspan="2">#</th>  
                                    <th class="text-center" rowspan="2">สถานะ</th>
                                    <th class="text-center" rowspan="2">Error</th>
                                    <th class="text-center" rowspan="2">เบิก/ส่ง</th>
                                    <th class="text-center" rowspan="2" width="10%">วัน-เวลา | Q</th>     
                                    <th class="text-center" rowspan="2">HN</th> 
                                    <th class="text-center" rowspan="2">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" rowspan="2">รายการต้องเรียกเก็บ</th>
                                    <th class="text-center" colspan="6">ค่ารักษา</th>                                     
                                    <th class="text-center bg-primary-soft" colspan="3">ข้อมูลการชดเชย</th>
                                </tr>
                                <tr>                                    
                                    <th class="text-center small">รวมทั้งหมด</th>
                                    <th class="text-center small">ชำระเอง</th>                                                                  
                                    <th class="text-center small">บริการเฉพาะ</th>
                                    <th class="text-center small">PPFS</th>
                                    <th class="text-center small">สมุนไพร</th>
                                    <th class="text-center text-primary small">รวมส่งเคลม</th>
                                    <th class="text-center bg-primary-soft small">STM ชดเชย</th> 
                                    <th class="text-center bg-primary-soft small">ผลต่าง</th> 
                                    <th class="text-center bg-primary-soft small">REP No.</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_uc_cr = 0; 
                                    $sum_ppfs = 0; 
                                    $sum_herb = 0; 
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-claim-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            {{-- แดง: ข้อมูลไม่ครบ --}}
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                             {{-- เหลือง: มี warnings (ins_ucs) --}}
                                             <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด">
                                                 <i class="bi bi-eye-fill"></i>
                                             </button>
                                        @elseif($row->endpoint_valid)
                                            {{-- เขียว: ข้อมูลครบ + ปิดสิทธิแล้ว --}}
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            {{-- เหลือง: ข้อมูลครบ แต่ยังไม่ปิดสิทธิ --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if(!empty($row->check_detail))
                                            @php
                                                $prefix = '';
                                                $badge_style = 'background-color: #dc3545; color: #fff;'; // Default red
                                                if (!empty($row->ec_status)) {
                                                    $first_char = substr($row->ec_status, 0, 1);
                                                    if (in_array($first_char, ['2', '3'])) {
                                                        $prefix = $first_char . '-';
                                                        if ($first_char === '3') {
                                                            $badge_style = 'background-color: #fd7e14; color: #fff;'; // Orange for 3
                                                        } else {
                                                            $badge_style = 'background-color: #f43f5e; color: #fff;'; // Rose red for 2
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <span class="badge fw-bold" style="font-size: 0.72rem; {{ $badge_style }}" title="พบข้อผิดพลาด e-Claim: {{ $row->check_detail }}">{{ $prefix }}{{ $row->check_detail }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->confirm_and_locked == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">ประสงค์เบิก:</span>
                                                @if($row->request_funds == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">พร้อมส่ง:</span>
                                                @if($row->confirm_and_locked == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="พร้อมส่ง Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ยังไม่พร้อมส่ง N"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}}</div>
                                        <div class="small fw-bold">Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-start small text-muted" style="font-size:0.7rem; max-width:180px;">{{ $row->claim_list }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>                                      
                                    <td class="text-end small">{{ number_format($row->uc_cr,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->ppfs,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->herb,2) }}</td> 
                                    <td class="text-end small fw-bold text-primary">{{ number_format($row->uc_cr + $row->ppfs + $row->herb, 2) }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    @php $diff = $row->receive_total - $row->uc_cr - $row->ppfs - $row->herb; @endphp
                                    <td class="text-end small fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td>
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_uc_cr += $row->uc_cr; 
                                    $sum_ppfs += $row->ppfs; 
                                    $sum_herb += $row->herb; 
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="8" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_uc_cr,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ppfs,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_herb,2) }}</th>
                                    <th class="text-end small fw-bold text-primary">{{ number_format($sum_uc_cr + $sum_ppfs + $sum_herb, 2) }}</th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total,2) }}</th>
                                    @php $total_diff = $sum_receive_total - $sum_uc_cr - $sum_ppfs - $sum_herb; @endphp
                                    <th class="text-end small fw-bold {{ $total_diff > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($total_diff, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div> 
            </div>
        </div>
    </div>

<script>
  function showLoading() {
      Swal.fire({
          title: 'กำลังโหลด...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });
  }
  function fetchData() {
      showLoading();
  }

  async function checkFdhBulk(e) {
      e.preventDefault();
      const searchItems = {!! json_encode(array_map(function($row) {
          return [
              'hn' => $row->hn,
              'seq' => $row->seq,
              'an' => ''
          ];
      }, $search)) !!};

      const claimItems = {!! json_encode(array_map(function($row) {
          return [
              'hn' => $row->hn,
              'seq' => $row->seq,
              'an' => ''
          ];
      }, $claim)) !!};

      const items = [...searchItems, ...claimItems];

      if (!items || items.length === 0) {
          Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
          return;
      }

      await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
          localStorage.setItem('active_tab', '#search');
          $('#form_indiv').submit();
      });
  }

  function alertAlreadyClosed(source) {
      Swal.fire({
          icon: 'info',
          title: 'ปิดสิทธิเรียบร้อยแล้ว',
          text: 'รายการนี้ปิดสิทธิโดย ' + source + ' เรียบร้อยแล้ว ไม่จำเป็นต้องส่งซ้ำอีกครั้ง',
          confirmButtonText: 'รับทราบ',
          confirmButtonColor: '#0d6efd'
      });
  }

  function pullNhsoData(vstdate, cid, vn) {
      Swal.fire({
          title: 'กำลังดึงข้อมูล...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading()
          }
      });

      fetch("{{ url('api/nhso_endpoint_pull_indiv') }}", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": "{{ csrf_token() }}",
              "Accept": "application/json"
          },
          body: JSON.stringify({
              vstdate: vstdate,
              cid: cid
          })
      })
          .then(async response => {
              const data = await response.json();
              if (!response.ok) {
                  throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
              }
              return data;
          })
          .then(data => {
              if (data.found) {
                  Swal.fire({
                      icon: 'success',
                      title: 'พบข้อมูลปิดสิทธิ',
                      text: data.message,
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => {
                      if (vn) {
                          showDetails(vn);
                      } else {
                          location.reload();
                      }
                  });
              } else {
                  Swal.fire({
                      icon: 'warning',
                      title: 'ไม่พบการปิดสิทธิจากระบบอื่น',
                      text: 'ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?',
                      showCancelButton: true,
                      confirmButtonColor: '#3085d6',
                      cancelButtonColor: '#6c757d',
                      confirmButtonText: 'ปิดสิทธิเลย',
                      cancelButtonText: 'ยกเลิก'
                  }).then(result => {
                      if (result.isConfirmed) {
                          pushNhsoData(cid, vstdate, vn);
                      }
                  });
              }
          })
          .catch(error => {
              Swal.fire({
                  icon: 'error',
                  title: 'เกิดข้อผิดพลาด',
                  text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
              });
          });
  }

  function pushNhsoData(cid, vstdate, vn) {
      Swal.fire({
          title: 'ยืนยันการส่งข้อมูล?',
          text: "ระบบจะดึงข้อมูลจาก HOSxP และส่งไปปิดสิทธิที่ สปสช.",
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'ตกลง, ส่งข้อมูล!',
          cancelButtonText: 'ยกเลิก'
      }).then((result) => {
          if (result.isConfirmed) {
              Swal.fire({
                  title: 'กำลังดำเนินการ...',
                  allowOutsideClick: false,
                  didOpen: () => {
                      Swal.showLoading()
                  }
              });

              $.ajax({
                  url: "{{ route('api.nhso.push_indiv') }}",
                  type: "POST",
                  data: {
                      _token: "{{ csrf_token() }}",
                      cid: cid,
                      vstdate: vstdate
                  },
                  success: function(response) {
                      if (response.status == 'success') {
                          Swal.fire({
                              icon: 'success',
                              title: 'สำเร็จ!',
                              text: 'ปิดสิทธิเรียบร้อยแล้ว',
                              timer: 1500,
                              showConfirmButton: false
                          }).then(() => {
                              if (vn) {
                                  showDetails(vn);
                              } else {
                                  location.reload();
                              }
                          });
                      } else {
                          Swal.fire({
                              icon: 'error',
                              title: 'ไม่สำเร็จ',
                              text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล'
                          });
                      }
                  },
                  error: function(xhr) {
                      let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                      if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                      Swal.fire({
                          icon: 'error',
                          title: 'เกิดข้อผิดพลาด',
                          text: msg
                      });
                  }
              });
          }
      });
  }
</script>

{{-- ✅ FDH Check Claim ------------------------------------------------------------ --}}
<script>
  function checkFdh(hn, seq) {

      Swal.fire({
          title: 'กำลังตรวจสอบสถานะ...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
      });

      $.ajax({
          url: "{{ url('/api/fdh/check-claim-indiv') }}",
          type: "POST",
          data: {
              hn: hn,
              seq: seq,
              _token: "{{ csrf_token() }}"
          },
          success: function (res) {

              const isSearchTab = $(`#td-status-search-${seq}`).length > 0;

              // ------------------------------
              // ✔ FDH ตอบสำเร็จ (200)
              // ------------------------------
              if (res.status === 200) {
                  Swal.fire({
                      icon: 'success',
                      title: 'ตรวจสอบสำเร็จ',
                      text: 'พบข้อมูลในระบบ FDH',
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => {
                      if (isSearchTab) {
                          localStorage.setItem('active_tab', '#claim');
                          fetchData();
                          $('#form_indiv').submit();
                      } else {
                          showDetails(seq);
                      }
                  });
                  return;
              }

              // ------------------------------
              // ✔ ไม่พบข้อมูล FDH (404)
              // ------------------------------
              if (res.status === 404 || res.status === 500) {
                  const statusText = res.body?.message_th ?? "ไม่มีรายการนี้ส่ง";
                  Swal.fire({
                      icon: 'warning',
                      title: 'ไม่พบข้อมูลในระบบ FDH',
                      text: statusText
                  }).then(() => {
                      showDetails(seq);
                  });
                  return;
              }

              // ------------------------------
              // ✔ ปัญหาฝั่งระบบ หรือ token/validate
              // ------------------------------
              if (res.status === 400) {
                  const statusText = res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้';
                  Swal.fire({
                      icon: 'error',
                      title: 'เกิดข้อผิดพลาด',
                      text: statusText
                  }).then(() => {
                      showDetails(seq);
                  });
                  return;
              }
          },

          error: function () {
              Swal.fire({
                  icon: 'error',
                  title: 'การเชื่อมต่อล้มเหลว',
                  text: 'ไม่สามารถเรียก API ได้ (Network Error)'
              });
          }
      });
  }
</script>

{{-- ── Validation Modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>เงื่อนไขที่ไม่ผ่านเกณฑ์การเคลม</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="validationModalBody">
                <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
            </div>
            <div class="modal-footer border-0">
                <small class="text-muted me-auto"><i class="bi bi-info-circle me-1"></i>กรุณาแก้ไขข้อมูลที่ HOSxP แล้วโหลดหน้าใหม่</small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Details Modal ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-clipboard2-pulse-fill me-2"></i>รายละเอียดการรับบริการ</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
            </div>
