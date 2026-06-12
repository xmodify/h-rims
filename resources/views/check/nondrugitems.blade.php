@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-briefcase-medical text-primary me-2"></i>
                จัดการค่ารักษาพยาบาล (Non-Drug Items)
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบและจัดการข้อมูลค่ารักษาพยาบาล (ไม่ใช่ยา) ในระบบ HOSxP</div>
        </div>
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <div class="filter-box" style="min-width: 250px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary">
                        <i class="bi bi-funnel-fill"></i>
                    </span>
                    <select class="form-select border-start-0 ps-0" id="incomeFilter" style="border-radius: 0 6px 6px 0;">
                        <option value="">-- กรองหมวดหมู่ทั้งหมด --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

@php
    $cntAll      = count($nondrugitems);
    $cntMatch    = collect($nondrugitems)->where('priceStatus', 'match')->count();
    $cntMismatch = collect($nondrugitems)->where('priceStatus', 'mismatch')->count();
    $cntNoAdp    = collect($nondrugitems)->whereIn('priceStatus', ['notfound', 'notype'])->count();
@endphp

    <!-- Active Items Card -->
    <div class="card dash-card mb-4">
        <div class="card-header bg-white pt-3 pb-0 border-0">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="mb-0 fw-bold text-success">
                    <i class="bi bi-check-circle-fill me-2"></i> ค่ารักษาพยาบาลที่เปิดใช้งาน
                </h6>
            </div>
            <!-- Status Tabs -->
            <ul class="nav nav-tabs border-0" id="priceStatusTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-4 fw-semibold" id="tab-all" data-status="" type="button">
                        ทั้งหมด <span class="badge bg-secondary ms-1">{{ $cntAll }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 fw-semibold text-success" id="tab-match" data-status="match" type="button">
                        <i class="bi bi-check-circle-fill me-1"></i>ตรงกัน <span class="badge bg-success ms-1">{{ $cntMatch }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 fw-semibold text-warning" id="tab-mismatch" data-status="mismatch" type="button">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>ต่างกัน <span class="badge bg-warning text-dark ms-1">{{ $cntMismatch }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 fw-semibold text-secondary" id="tab-noadp" data-status="noadp" type="button">
                        <i class="bi bi-question-circle-fill me-1"></i>ไม่พบ ADP <span class="badge bg-secondary ms-1">{{ $cntNoAdp }}</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4 pt-3">
            <div class="table-responsive">            
                <table id="nondrugitems" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">หมวดค่ารักษาพยาบาล</th>  
                            <th class="text-center">รหัส</th>
                            <th class="text-center">ชื่อรายการ</th>  
                            <th class="text-center">ราคา HOSxP</th>     
                            <th class="text-center">สถานะราคา</th>
                            <th class="text-center">ประเภทการชำระ</th>
                            <th class="text-center">Billcode</th>
                            <th class="text-center">ADP Code</th> 
                            <th class="text-center">ADP Type</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($nondrugitems as $row) 
                        @php
                            $status = $row->priceStatus ?? 'notype';
                            $rp = $row->rulePrices ?? [];
                            $rpJson = json_encode([
                                'icode'      => $row->icode,
                                'name'       => $row->name,
                                'hosxpPrice' => floatval($row->price  ?? 0),
                                'hosxpPrice2'=> floatval($row->price2 ?? 0),
                                'hosxpPrice3'=> floatval($row->price3 ?? 0),
                                'hosxpPriceSss'=> floatval($row->price_sss ?? 0),
                                'hosxpPriceLgo'=> floatval($row->price_lgo ?? 0),
                                'hosxpPriceUcs'=> floatval($row->price_ucs ?? 0),
                                'ipdPrice'   => floatval($row->ipd_price  ?? 0),
                                'ipdPrice2'  => floatval($row->ipd_price2 ?? 0),
                                'ipdPrice3'  => floatval($row->ipd_price3 ?? 0),
                                'adpCode'    => $row->nhso_adp_code,
                                'adpName'    => $row->nhso_adp_code_name,
                                'adpType'    => $row->nhso_adp_type_name,
                                'ruleName'   => $row->ruleName ?? '',
                                'status'     => $status,
                                'rulePrices' => $rp,
                                'v4_override'=> $row->v4_override ?? [],
                                'v4_all_overrides' => $row->v4_all_overrides ?? [],
                                'has_v4_table' => $hasPttypeItemsPrice,
                            ], JSON_UNESCAPED_UNICODE);
                        @endphp
                        <tr data-price-status="{{ $status }}">
                            <td class="text-start small text-muted lh-1">{{$row->income}}</td> 
                            <td class="text-center fw-bold">{{$row->icode}}</td>                                
                            <td class="text-start fw-bold text-dark">{{$row->name}}</td>            
                            <td class="text-end text-primary fw-bold">
                                @php
                                    $displayPrice = $row->price;
                                    // Under V4 or V3, if we want to prioritize displaying OFC price in the main column:
                                    if (isset($row->price2) && floatval($row->price2) > 0.1) {
                                        $displayPrice = $row->price2;
                                    }
                                @endphp
                                {{ number_format($displayPrice, 2) }}
                            </td>
                            <td class="text-center">
                                @if($status === 'match')
                                    <button class="btn btn-sm btn-link p-0 price-detail-btn" data-info="{{ $rpJson }}" title="ดูรายละเอียดราคา">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>ตรงกัน</span>
                                    </button>
                                @elseif($status === 'mismatch')
                                    <button class="btn btn-sm btn-link p-0 price-detail-btn" data-info="{{ $rpJson }}" title="ดูรายละเอียดราคา">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-triangle-fill me-1"></i>ต่างกัน</span>
                                    </button>
                                @elseif($status === 'notfound' || $status === 'notype')
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-question-circle-fill me-1"></i>ไม่พบ ADP</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $paidstCode = $row->paidst ?? '';
                                    $paidstName = $row->paidst_name ?? '-';
                                    $paidstClass = match($paidstCode) {
                                        '02' => 'bg-primary-subtle text-primary',
                                        '01' => 'bg-success-subtle text-success',
                                        '03' => 'bg-danger-subtle text-danger',
                                        '04' => 'bg-warning-subtle text-warning',
                                        '00' => 'bg-secondary-subtle text-secondary',
                                        default => 'bg-light text-muted',
                                    };
                                @endphp
                                @if($paidstCode)
                                    <span class="badge {{ $paidstClass }} border" title="{{ $paidstCode }}">{{ $paidstName }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{$row->billcode}}</span></td>
                            <td class="text-center small">{{$row->nhso_adp_code}}</td> 
                            <td class="text-center"><span class="badge bg-info-subtle text-info">{{$row->nhso_adp_type_name}}</span></td>
                        </tr>
                        @endforeach                 
                    </tbody>
                </table>         
            </div>
        </div> 
    </div>

    <!-- Inactive Items Card -->
    <div class="card dash-card">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold text-secondary">
                <i class="bi bi-slash-circle me-2"></i> ค่ารักษาพยาบาลที่ปิดใช้งาน
            </h6>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="table-responsive">            
                <table id="nondrugitems_non" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center text-secondary">หมวดค่ารักษาพยาบาล</th>  
                            <th class="text-center text-secondary">รหัส</th>
                            <th class="text-center text-secondary">ชื่อรายการ</th>  
                            <th class="text-center text-secondary">ราคา</th>     
                            <th class="text-center text-secondary">ประเภทการชำระ</th>
                            <th class="text-center text-secondary">Billcode</th>
                            <th class="text-center text-secondary">ADP Code</th> 
                            <th class="text-center text-secondary">ADP Name</th>
                            <th class="text-center text-secondary">ADP Type</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($nondrugitems_non as $row) 
                        <tr class="opacity-75">
                            <td class="text-start small text-muted lh-1">{{$row->income}}</td> 
                            <td class="text-center">{{$row->icode}}</td>                                
                            <td class="text-start">{{$row->name}}</td>            
                            <td class="text-end fw-bold text-secondary">{{number_format($row->price,2)}}</td>
                            <td class="text-center">
                                @php
                                    $paidstCode = $row->paidst ?? '';
                                    $paidstName = $row->paidst_name ?? '-';
                                    $paidstClass = match($paidstCode) {
                                        '02' => 'bg-primary-subtle text-primary',
                                        '01' => 'bg-success-subtle text-success',
                                        '03' => 'bg-danger-subtle text-danger',
                                        '04' => 'bg-warning-subtle text-warning',
                                        '00' => 'bg-secondary-subtle text-secondary',
                                        default => 'bg-light text-muted',
                                    };
                                @endphp
                                @if($paidstCode)
                                    <span class="badge {{ $paidstClass }} border" title="{{ $paidstCode }}">{{ $paidstName }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{$row->billcode}}</td>
                            <td class="text-center small">{{$row->nhso_adp_code}}</td> 
                            <td class="text-start small text-muted">{{$row->nhso_adp_code_name}}</td>
                            <td class="text-center small">{{$row->nhso_adp_type_name}}</td>
                        </tr>
                        @endforeach                 
                    </tbody>
                </table>         
            </div>
        </div> 
    </div>
</div>

<!-- Price Detail Modal -->
<div class="modal fade" id="priceDetailModal" tabindex="-1" aria-labelledby="priceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="priceDetailModalLabel">
                        <i class="bi bi-currency-exchange me-2 text-primary"></i>เปรียบราคา HOSxP กับ Rules
                    </h6>
                    <small class="text-muted" id="modalAdpCode"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Item Info -->
                <div class="mb-3 p-3 bg-light rounded-3">
                    <div class="fw-bold text-dark" id="modalItemName"></div>
                    <div class="small text-muted mt-1" id="modalRuleName"></div>
                    <div class="small text-muted" id="modalAdpType"></div>
                </div>

                <!-- Status Badge -->
                <div class="text-center mb-3" id="modalStatusBadge"></div>

                <!-- Comparison Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" id="comparisonTable">
                        <thead class="table-light">
                            <tr id="modalTableHeaders">
                                <!-- Headers will be rendered dynamically by JS -->
                            </tr>
                        </thead>
                        <tbody id="modalPricesBody"></tbody>
                    </table>
                </div>
                <div class="mt-2 p-2 bg-light rounded-2 small text-muted" id="modalPriceFooterInfo">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>คำอธิบายราคา:</strong> แสดงการเปรียบเทียบราคาในฐานข้อมูล HOSxP เทียบกับราคาอ้างอิงจาก Rules
                </div>

                <!-- Subtable for pttype_items_price overrides -->
                <div id="v4OverridesSection" class="mt-4" style="display: none;">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-tags-fill me-2 text-info"></i>ราคาพิเศษตามกลุ่มสิทธิ์ (pttype_items_price)
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width:35%">กลุ่มสิทธิ์ (pttype_price_group)</th>
                                    <th class="bg-info-subtle" style="width:20%">ราคาพิเศษ HOSxP</th>
                                    <th class="bg-success-subtle" style="width:25%">เกณฑ์เปรียบเทียบ ADP</th>
                                    <th style="width:20%">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="v4OverridesBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')  
  <script>
    $(document).ready(function () {
      var table1 = $('#nondrugitems').DataTable({
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
              title: 'ค่ารักษาพยาบาล ที่เปิดใช้งาน'
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

      var table2 = $('#nondrugitems_non').DataTable({
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
              text: 'Excel',
              className: 'btn btn-success',
              title: 'ค่ารักษาพยาบาล ที่ปิดใช้งาน'
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

      // Filter by income category
      $('#incomeFilter').on('change', function () {
          var val = $(this).val();
          table1.column(0).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
          table2.column(0).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
      });

      // Tab-based status filter
      var activeStatus = '';
      $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
          if (settings.nTable.id !== 'nondrugitems') return true;
          if (!activeStatus) return true;
          var row = $(table1.row(dataIndex).node());
          var status = row.data('price-status');
          if (activeStatus === 'noadp') {
              return status === 'notfound' || status === 'notype';
          }
          return status === activeStatus;
      });

      $('#priceStatusTabs button').on('click', function () {
          $('#priceStatusTabs button').removeClass('active');
          $(this).addClass('active');
          activeStatus = $(this).data('status');
          table1.draw();
      });

      // Price Detail Modal
      function fmtPrice(v) {
          v = parseFloat(v) || 0;
          return v > 0 ? '<span class="fw-bold">' + v.toLocaleString('th-TH',{minimumFractionDigits:2}) + '</span>'
                       : '<span class="text-muted">-</span>';
      }

      $(document).on('click', '.price-detail-btn', function() {
          var info = JSON.parse($(this).attr('data-info'));
          $('#modalItemName').html('<span class="text-primary">[' + info.icode + ']</span> ' + info.name);
          $('#modalAdpCode').text('ADP: ' + (info.adpCode||'-') + (info.adpName ? ' | '+info.adpName : ''));
          $('#modalRuleName').text(info.ruleName ? 'Rules: ' + info.ruleName : '');
          $('#modalAdpType').text('Type: ' + (info.adpType||'-'));

          var statusHtml = '';
          if (info.status==='match')    statusHtml = '<span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>ราคาตรงกันกับ Rules</span>';
          else if (info.status==='mismatch') statusHtml = '<span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i>ราคาต่างจาก Rules</span>';
          else if (info.status==='notfound' || info.status==='notype') statusHtml = '<span class="badge bg-secondary px-3 py-2"><i class="bi bi-question-circle me-1"></i>ไม่พบ ADP</span>';
          $('#modalStatusBadge').html(statusHtml);

          // We determine V4 mode if the environment has the V4 table
          var isV4Db = info.has_v4_table === true;
          var hasV4Overrides = info.v4_override && info.v4_override.length > 0;
          var tbody = '';
          var headers = '';

          // HOSxP mode: Show only one main row representing the default base price
          headers = '<th class="text-center" style="width:25%">โครงสร้างราคา HOSxP</th>' +
                    '<th class="text-center bg-primary-subtle" style="width:20%">ราคา OPD</th>' +
                    '<th class="text-center bg-success-subtle" style="width:35%">เกณฑ์เปรียบเทียบ ADP</th>' +
                    '<th class="text-center" style="width:20%">ราคา IPD</th>';

          var basePrice = parseFloat(info.hosxpPrice||0);
          var baseIpd   = parseFloat(info.ipdPrice||0);

          // Render rule prices matching this base price
          var rulesTextList = [];
          $.each(info.rulePrices || {}, function(right, rPrice) {
              var pr = parseFloat(rPrice);
              if (pr > 0) {
                  var isMatch = Math.abs(pr - basePrice) < 0.1 || parseInt(pr) === parseInt(basePrice);
                  var badgeClass = isMatch ? 'bg-success-subtle text-success border border-success-subtle' 
                                           : 'bg-warning-subtle text-warning border border-warning-subtle';
                  rulesTextList.push('<span class="badge ' + badgeClass + ' me-1" style="font-size:0.75rem;">' + 
                                     right + '=' + pr.toLocaleString('th-TH') + '</span>');
              }
          });

          var matchesCell = rulesTextList.length > 0 ? rulesTextList.join(' ') : '<span class="text-muted small">- ไม่ระบุใน Rules -</span>';

          tbody += '<tr>'+
              '<td class="fw-semibold small">ราคากรมบัญชีกลาง OFC</td>'+
              '<td class="text-end bg-primary-subtle">'+fmtPrice(basePrice)+'</td>'+
              '<td class="text-start bg-success-subtle lh-lg">'+matchesCell+'</td>'+
              '<td class="text-end text-muted small">'+fmtPrice(baseIpd)+'</td>'+
              '</tr>';

          if (isV4Db) {
              $('#modalPriceFooterInfo').html('<i class="bi bi-info-circle me-1"></i> <span class="badge bg-info text-dark">V4 Active</span> แสดงราคากลางหลักกรมบัญชีกลาง OFC จาก <code>nondrugitems.price</code>');
          } else {
              $('#modalPriceFooterInfo').html('<i class="bi bi-info-circle me-1"></i> <span class="badge bg-secondary">V3 Active</span> แสดงราคากลางหลักกรมบัญชีกลาง OFC จาก <code>nondrugitems.price</code>');
          }

          if (!tbody) tbody = '<tr><td colspan="5" class="text-center text-muted py-3">ไม่มีข้อมูลราคาที่จะแสดง</td></tr>';
          $('#modalTableHeaders').html(headers);
          $('#modalPricesBody').html(tbody);

          // Render sub-table for pttype_items_price overrides if available
          var overridesHtml = '';
          var hasAnyOverrides = info.v4_all_overrides && info.v4_all_overrides.length > 0;
          if (isV4Db && hasAnyOverrides) {
              $.each(info.v4_all_overrides, function(idx, item) {
                  var grpId = item.pttype_price_group_id;
                  var grpName = item.pttype_price_group_name || ('กลุ่มสิทธิ์ที่ ' + grpId);
                  var priceVal = parseFloat(item.price || 0);

                  // Map groups to rules keys:
                  var ruleKey = '';
                  var grpLower = grpName.toLowerCase();
                  if (grpId == 2 || grpLower.indexOf('ucs') !== -1 || grpLower.indexOf('บัตรทอง') !== -1 || grpLower.indexOf('หลักประกัน') !== -1) {
                      ruleKey = 'UCS';
                  } else if (grpId == 3 || grpLower.indexOf('ofc') !== -1 || grpLower.indexOf('ข้าราชการ') !== -1 || grpLower.indexOf('กรมบัญชีกลาง') !== -1) {
                      ruleKey = 'OFC';
                  } else if (grpId == 4 || grpLower.indexOf('sss') !== -1 || grpLower.indexOf('ประกันสังคม') !== -1) {
                      ruleKey = 'SSS';
                  } else if (grpId == 5 || grpLower.indexOf('lgo') !== -1 || grpLower.indexOf('อปท') !== -1 || grpLower.indexOf('ส่วนท้องถิ่น') !== -1) {
                      ruleKey = 'LGO';
                  } else if (grpId == 1 || grpLower.indexOf('ชำระเงินเอง') !== -1 || grpLower.indexOf('cash') !== -1 || grpLower.indexOf('fs') !== -1) {
                      ruleKey = 'FS';
                  }

                  var rulePrice = 0;
                  var rulePriceText = '<span class="text-muted">-</span>';
                  if (ruleKey && info.rulePrices && info.rulePrices[ruleKey] !== undefined) {
                      rulePrice = parseFloat(info.rulePrices[ruleKey]);
                      if (rulePrice > 0) {
                          rulePriceText = '<span class="fw-bold">' + rulePrice.toLocaleString('th-TH', {minimumFractionDigits: 2}) + '</span> (' + ruleKey + ')';
                      }
                  }

                  var statusBadge = '';
                  if (rulePrice > 0) {
                      var isMatch = Math.abs(rulePrice - priceVal) < 0.1 || parseInt(rulePrice) === parseInt(priceVal);
                      if (isMatch) {
                          statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>ตรงกัน</span>';
                      } else {
                          statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-triangle-fill me-1"></i>ต่างกัน</span>';
                      }
                  } else {
                      statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">ไม่มีราคาใน Rules</span>';
                  }

                  overridesHtml += '<tr>' +
                      '<td class="fw-semibold text-start">' + grpName + ' <span class="badge bg-light text-muted border" style="font-size:0.7rem;">ID: ' + grpId + '</span></td>' +
                      '<td class="text-end bg-info-subtle">' + fmtPrice(priceVal) + '</td>' +
                      '<td class="text-start bg-success-subtle">' + rulePriceText + '</td>' +
                      '<td class="text-center">' + statusBadge + '</td>' +
                      '</tr>';
              });
              $('#v4OverridesBody').html(overridesHtml);
              $('#v4OverridesSection').show();
          } else {
              $('#v4OverridesSection').hide();
          }

          $('#priceDetailModal').modal('show');
      });
    });
  </script>
@endpush 
