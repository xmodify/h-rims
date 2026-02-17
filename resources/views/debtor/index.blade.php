@extends('layouts.app')

@section('content')
    <!-- Page Header & Actions -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-person-lines-fill me-2"></i>
                ลูกหนี้ค่ารักษาพยาบาล{{$hospital_name}} ({{$hospital_code}})
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-warning btn-sm shadow-sm" href="{{ url('debtor/check_income') }}" target="_blank">
                <i class="bi bi-search me-1"></i> ตรวจสอบค่ารักษาพยาบาล
            </a>
            <a class="btn btn-outline-danger btn-sm shadow-sm" href="{{ url('debtor/check_nondebtor') }}" target="_blank">
                <i class="bi bi-exclamation-circle me-1"></i> รอยืนยันลูกหนี้
            </a>
            <a class="btn btn-outline-success btn-sm shadow-sm" href="{{ url('debtor/summary') }}" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> สรุปบัญชีลูกหนี้
            </a>
            @auth
                @if(auth()->user()->status === 'admin')
                    <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#LockdebtorModal">
                        <i class="bi bi-lock-fill me-1"></i> Lock ลูกหนี้
                    </button>
                @endif
            @endauth
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card dash-card border-0">
        <div class="card-body px-4 pb-4 pt-4">
            <div class="row">
                <!-- OP Column -->
                <div class="col-md-6 border-end">
                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill me-2"></i>ผู้ป่วยนอก
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <tbody>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_103') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ</a></td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_109') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.109-ลูกหนี้-ระบบปฏิบัติการฉุกเฉิน</a></td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_201') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.201-ลูกหนี้ค่ารักษา UC-OP ใน CUP</a></td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_203') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.203-ลูกหนี้ค่ารักษา UC-OP นอก CUP (ในจังหวัดสังกัด สธ.)</a></td>
                                </tr> 
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.204-ลูกหนี้ค่ารักษา UC-OP นอก CUP (ต่างจังหวัดสังกัด สธ.)</td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_209') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.209-ลูกหนี้ค่ารักษา ด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_216') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)</a></td>
                                </tr>   
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.222-ลูกหนี้ค่ารักษา OP-Refer</td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_301') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_303') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.303-ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย สังกัด สป.สธ.</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_307') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.307-ลูกหนี้ค่ารักษา ประกันสังคม-กองทุนทดแทน</a></td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_309') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.309-ลูกหนี้ค่ารักษา ประกันสังคม-ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP</a></td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_401') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.401-ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง OP</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_501') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.501-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_503') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.503-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP</a></td>
                                </tr>    
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.505-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว เบิกจากส่วนกลาง OP</td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_701') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.701-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ OP ใน CUP</a></td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_702') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.702-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ OP นอก CUP</a></td>
                                </tr>   
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.703-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ เบิกจากส่วนกลาง OP</td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_106') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.106-ลูกหนี้ค่ารักษา ชําระเงิน OP</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_108') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_110') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.110-ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP</a></td>
                                </tr>     
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050102.201-ลูกหนี้ค่ารักษา UC-OP นอกสังกัด สธ.</td>
                                </tr>   
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050102.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย ต่างสังกัด สป.สธ.</td>
                                </tr>       
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_602') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.602-ลูกหนี้ค่ารักษา พรบ.รถ OP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_801') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.801-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.OP</a></td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_803') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.803-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ OP</a></td>
                                </tr>                                                                  
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- IP Column -->
                <div class="col-md-6">
                    <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill-add me-2"></i>ผู้ป่วยใน
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <tbody>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_202') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.202-ลูกหนี้ค่ารักษา UC-IP</a></td>
                                </tr>       
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_217') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.217-ลูกหนี้ค่ารักษา UC-IP บริการเฉพาะ (CR)</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_302') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.302-ลูกหนี้ค่ารักษา ประกันสังคม IP เครือข่าย</a></td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_304') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.304-ลูกหนี้ค่ารักษา ประกันสังคม IP นอกเครือข่าย สังกัด สป.สธ.</a></td>
                                </tr>         
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_308') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.308-ลูกหนี้ค่ารักษา ประกันสังคม 72 ชั่วโมงแรก</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_310') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.310-ลูกหนี้ค่ารักษา ประกันสังคม ค่าใช้จ่ายสูง IP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_402') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.402-ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_502') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.502-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว IP</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_504') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.504-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว IP นอก CUP</a></td>
                                </tr>                  
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.506-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าวเบิกจากส่วนกลาง IP</td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_704') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.704-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ เบิกจากส่วนกลาง IP</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_107') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.107-ลูกหนี้ค่ารักษา ชําระเงิน IP</a></td>
                                </tr>        
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_109') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.109-ลูกหนี้ค่ารักษา เบิกต้นสังกัด IP</a></td>
                                </tr>        
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_111') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.111-ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น IP</a></td>
                                </tr>  
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050102.302-ลูกหนี้ค่ารักษา ประกันสังคม IP-นอกเครือข่าย ต่างสังกัด สป.สธ.</td>
                                </tr>           
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_603') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.603-ลูกหนี้ค่ารักษา พรบ.รถ IP</a></td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_802') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.802-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.IP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_804') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.804-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ IP</a></td>
                                </tr>                                           
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

  {{-- Modal Lock ลูกหนี้ --}}
  <div class="modal fade" id="LockdebtorModal" tabindex="-1">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title">Lock ลูกหนี้</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <div class="mb-3">
                      <label class="form-label">วันที่เริ่มต้น</label>
                      <input type="hidden" id="start_date">
                      <input type="text" id="start_date_picker" class="form-control datepicker_th" placeholder="วว/ดด/ปปปป" autocomplete="off" readonly>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">วันที่สิ้นสุด</label>
                      <input type="hidden" id="end_date">
                      <input type="text" id="end_date_picker" class="form-control datepicker_th" placeholder="วว/ดด/ปปปป" autocomplete="off" readonly>
                  </div>
              </div>
              <div class="modal-footer">
                  <button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                  <button class="btn btn-danger" id="lockDebtorBtn">ยืนยัน Lock</button>
              </div>
          </div>
      </div>
  </div>

  {{-- Script Lock ลูกหนี้ --}}
@push('scripts')
  <script>
    $(document).ready(function() {
        // Initialize Datepicker Thai
        $('.datepicker_th').datepicker({
            format: 'yyyy-mm-dd',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true
        });

        // Sync Changes from Picker to Hidden Input
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

        $('#lockDebtorBtn').click(function () {
            const start = $('#start_date').val();
            const end = $('#end_date').val();
            const startDisplay = $('#start_date_picker').val();
            const endDisplay = $('#end_date_picker').val();

            if (!start || !end) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกวันที่ให้ครบ', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยัน Lock ลูกหนี้?',
                text: `ช่วงวันที่ ${startDisplay} ถึง ${endDisplay}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Lock',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {

                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch(`{{ url('admin/debtor/lock_debtor') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            start_date: start,
                            end_date: end
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            html: `
                                Lock ลูกหนี้เรียบร้อย<br>
                                <b>ช่วงวันที่:</b> ${data.start_date} - ${data.end_date}<br>
                                <b>จำนวนตารางที่อัปเดต:</b> ${data.tables}<br>
                                <b>จำนวนรายการที่ถูก Lock:</b> ${data.rows}
                            `
                        });
                    })
                    .catch(err => {
                        Swal.fire('ผิดพลาด', err.toString(), 'error');
                    });
                }
            });
        });
    });
  </script>
@endpush

@endsection
