@extends('layouts.app')

@section('content')
    <!-- Page Header & Actions -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-person-lines-fill me-2"></i>
                เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅ{{$hospital_name}} ({{$hospital_code}})
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-warning btn-sm shadow-sm" href="{{ url('debtor/check_income') }}" target="_blank">
                <i class="bi bi-search me-1"></i> เธ•เธฃเธงเธเธชเธญเธเธเนเธฒเธฃเธฑเธเธฉเธฒเธเธขเธฒเธเธฒเธฅ
            </a>
            <a class="btn btn-outline-danger btn-sm shadow-sm" href="{{ url('debtor/check_nondebtor') }}" target="_blank">
                <i class="bi bi-exclamation-circle me-1"></i> เธฃเธญเธขเธทเธเธขเธฑเธเธฅเธนเธเธซเธเธตเน
            </a>
            <a class="btn btn-outline-success btn-sm shadow-sm" href="{{ url('debtor/summary') }}" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> เธชเธฃเธธเธเธเธฑเธเธเธตเธฅเธนเธเธซเธเธตเน
            </a>
            @auth
                @if(auth()->user()->status === 'admin')
                    <button type="button" class="btn btn-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#LockdebtorModal">
                        <i class="bi bi-lock-fill me-1"></i> Lock เธฅเธนเธเธซเธเธตเน
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
                        <i class="bi bi-person-fill me-2"></i>เธเธนเนเธเนเธงเธขเธเธญเธ
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <tbody>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_103') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.103-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธ•เธฃเธงเธเธชเธธเธเธ เธฒเธ เธซเธเนเธงเธขเธเธฒเธเธ เธฒเธเธฃเธฑเธ</a></td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_109') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.109-เธฅเธนเธเธซเธเธตเน-เธฃเธฐเธเธเธเธเธดเธเธฑเธ•เธดเธเธฒเธฃเธเธธเธเน€เธเธดเธ</a></td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_201') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.201-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เนเธ CUP</a></td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_203') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.203-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เธเธญเธ CUP (เนเธเธเธฑเธเธซเธงเธฑเธ”เธชเธฑเธเธเธฑเธ” เธชเธ.)</a></td>
                                </tr> 
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.204-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เธเธญเธ CUP (เธ•เนเธฒเธเธเธฑเธเธซเธงเธฑเธ”เธชเธฑเธเธเธฑเธ” เธชเธ.)</td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_209') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.209-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธ”เนเธฒเธเธเธฒเธฃเธชเธฃเนเธฒเธเน€เธชเธฃเธดเธกเธชเธธเธเธ เธฒเธเนเธฅเธฐเธเนเธญเธเธเธฑเธเนเธฃเธ (P&P)</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_216') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.216-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เธเธฃเธดเธเธฒเธฃเน€เธเธเธฒเธฐ (CR)</a></td>
                                </tr>   
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.222-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ OP-Refer</td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_301') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.301-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก OP-เน€เธเธฃเธทเธญเธเนเธฒเธข</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_303') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.303-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก OP-เธเธญเธเน€เธเธฃเธทเธญเธเนเธฒเธข เธชเธฑเธเธเธฑเธ” เธชเธ.เธชเธ.</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_307') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.307-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธเธญเธเธ—เธธเธเธ—เธ”เนเธ—เธ</a></td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_309') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.309-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก-เธเนเธฒเนเธเนเธเนเธฒเธขเธชเธนเธ/เธญเธธเธเธฑเธ•เธดเน€เธซเธ•เธธ/เธเธธเธเน€เธเธดเธ OP</a></td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_401') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.401-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธเธเธฃเธกเธเธฑเธเธเธตเธเธฅเธฒเธ OP</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_501') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.501-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง OP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_503') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.503-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง OP เธเธญเธ CUP</a></td>
                                </tr>    
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.505-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง เน€เธเธดเธเธเธฒเธเธชเนเธงเธเธเธฅเธฒเธ OP</td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_701') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.701-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด OP เนเธ CUP</a></td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_702') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.702-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด OP เธเธญเธ CUP</a></td>
                                </tr>   
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.703-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด เน€เธเธดเธเธเธฒเธเธชเนเธงเธเธเธฅเธฒเธ OP</td>
                                </tr> 
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_106') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.106-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ OP</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_108') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.108-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธ•เนเธเธชเธฑเธเธเธฑเธ” OP</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_110') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.110-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธเธซเธเนเธงเธขเธเธฒเธเธญเธทเนเธ OP</a></td>
                                </tr>     
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050102.201-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-OP เธเธญเธเธชเธฑเธเธเธฑเธ” เธชเธ.</td>
                                </tr>   
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050102.301-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก OP-เธเธญเธเน€เธเธฃเธทเธญเธเนเธฒเธข เธ•เนเธฒเธเธชเธฑเธเธเธฑเธ” เธชเธ.เธชเธ.</td>
                                </tr>       
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_602') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.602-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธ.เธฃเธ– OP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_801') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.801-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.OP</a></td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_803') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.803-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.เธฃเธนเธเนเธเธเธเธดเน€เธจเธฉ OP</a></td>
                                </tr>                                                                  
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- IP Column -->
                <div class="col-md-6">
                    <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill-add me-2"></i>เธเธนเนเธเนเธงเธขเนเธ
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <tbody>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_202') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.202-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-IP</a></td>
                                </tr>       
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_217') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.217-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ UC-IP เธเธฃเธดเธเธฒเธฃเน€เธเธเธฒเธฐ (CR)</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_302') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.302-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก IP เน€เธเธฃเธทเธญเธเนเธฒเธข</a></td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_304') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.304-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก IP เธเธญเธเน€เธเธฃเธทเธญเธเนเธฒเธข เธชเธฑเธเธเธฑเธ” เธชเธ.เธชเธ.</a></td>
                                </tr>         
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_308') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.308-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก 72 เธเธฑเนเธงเนเธกเธเนเธฃเธ</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_310') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.310-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก เธเนเธฒเนเธเนเธเนเธฒเธขเธชเธนเธ IP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_402') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.402-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ-เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธเธฃเธกเธเธฑเธเธเธตเธเธฅเธฒเธ IP</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_502') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.502-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง IP</a></td>
                                </tr>   
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_504') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.504-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธง IP เธเธญเธ CUP</a></td>
                                </tr>                  
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050101.506-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธเธ•เนเธฒเธเธ”เนเธฒเธงเนเธฅเธฐเนเธฃเธเธเธฒเธเธ•เนเธฒเธเธ”เนเธฒเธงเน€เธเธดเธเธเธฒเธเธชเนเธงเธเธเธฅเธฒเธ IP</td>
                                </tr>    
                                <tr>
                                    <td><a href="{{ url('debtor/1102050101_704') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050101.704-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธธเธเธเธฅเธ—เธตเนเธกเธตเธเธฑเธเธซเธฒเธชเธ–เธฒเธเธฐเนเธฅเธฐเธชเธดเธ—เธเธด เน€เธเธดเธเธเธฒเธเธชเนเธงเธเธเธฅเธฒเธ IP</a></td>
                                </tr>      
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_107') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.107-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเนเธฒเธฃเธฐเน€เธเธดเธ IP</a></td>
                                </tr>        
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_109') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.109-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธ•เนเธเธชเธฑเธเธเธฑเธ” IP</a></td>
                                </tr>        
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_111') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.111-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธเธซเธเนเธงเธขเธเธฒเธเธญเธทเนเธ IP</a></td>
                                </tr>  
                                <tr>
                                    <td class="text-danger fw-bold py-2"><i class="bi bi-x-circle-fill me-2 small"></i>1102050102.302-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธฐเธเธฑเธเธชเธฑเธเธเธก IP-เธเธญเธเน€เธเธฃเธทเธญเธเนเธฒเธข เธ•เนเธฒเธเธชเธฑเธเธเธฑเธ” เธชเธ.เธชเธ.</td>
                                </tr>           
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_603') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.603-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เธเธฃเธ.เธฃเธ– IP</a></td>
                                </tr>  
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_802') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.802-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.IP</a></td>
                                </tr>     
                                <tr>
                                    <td><a href="{{ url('debtor/1102050102_804') }}" target="_blank" class="text-decoration-none text-dark d-block py-1"><i class="bi bi-caret-right-fill text-secondary me-2 small"></i>1102050102.804-เธฅเธนเธเธซเธเธตเนเธเนเธฒเธฃเธฑเธเธฉเธฒ เน€เธเธดเธเธเนเธฒเธขเธ•เธฃเธ เธญเธเธ—.เธฃเธนเธเนเธเธเธเธดเน€เธจเธฉ IP</a></td>
                                </tr>                                           
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

  {{-- Modal Lock เธฅเธนเธเธซเธเธตเน --}}
  <div class="modal fade" id="LockdebtorModal" tabindex="-1">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title">Lock เธฅเธนเธเธซเธเธตเน</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <div class="mb-3">
                      <label class="form-label">เธงเธฑเธเธ—เธตเนเน€เธฃเธดเนเธกเธ•เนเธ</label>
                      <input type="date" id="start_date" class="form-control" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">เธงเธฑเธเธ—เธตเนเธชเธดเนเธเธชเธธเธ”</label>
                      <input type="date" id="end_date" class="form-control" required>
                  </div>
              </div>
              <div class="modal-footer">
                  <button class="btn btn-secondary" data-bs-dismiss="modal">เธขเธเน€เธฅเธดเธ</button>
                  <button class="btn btn-danger" id="lockDebtorBtn">เธขเธทเธเธขเธฑเธ Lock</button>
              </div>
          </div>
      </div>
  </div>

  {{-- Script Lock เธฅเธนเธเธซเธเธตเน --}}
  <script>
    document.getElementById('lockDebtorBtn').addEventListener('click', function () {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;

        if (!start || !end) {
            Swal.fire('เนเธเนเธเน€เธ•เธทเธญเธ', 'เธเธฃเธธเธ“เธฒเน€เธฅเธทเธญเธเธงเธฑเธเธ—เธตเนเนเธซเนเธเธฃเธ', 'warning');
            return;
        }

        Swal.fire({
            title: 'เธขเธทเธเธขเธฑเธ Lock เธฅเธนเธเธซเธเธตเน?',
            text: `เธเนเธงเธเธงเธฑเธเธ—เธตเน ${start} เธ–เธถเธ ${end}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Lock',
            cancelButtonText: 'เธขเธเน€เธฅเธดเธ',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {

                Swal.fire({
                    title: 'เธเธณเธฅเธฑเธเธ”เธณเน€เธเธดเธเธเธฒเธฃ...',
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
                        title: 'เธชเธณเน€เธฃเนเธ',
                        html: `
                            Lock เธฅเธนเธเธซเธเธตเนเน€เธฃเธตเธขเธเธฃเนเธญเธข<br>
                            <b>เธเนเธงเธเธงเธฑเธเธ—เธตเน:</b> ${data.start_date} - ${data.end_date}<br>
                            <b>เธเธณเธเธงเธเธ•เธฒเธฃเธฒเธเธ—เธตเนเธญเธฑเธเน€เธ”เธ•:</b> ${data.tables}<br>
                            <b>เธเธณเธเธงเธเธฃเธฒเธขเธเธฒเธฃเธ—เธตเนเธ–เธนเธ Lock:</b> ${data.rows}
                        `
                    });
                })
                .catch(err => {
                    Swal.fire('เธเธดเธ”เธเธฅเธฒเธ”', err.toString(), 'error');
                });
            }
        });
    });
  </script>

@endsection
