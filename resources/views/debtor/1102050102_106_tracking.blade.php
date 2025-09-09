<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title >Tracking-1102050102.106</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <!-- <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">

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

    <div class="container">  
        <div class="row"  >            
            <div class="col-sm-12"> 
                <div class="alert alert-success text-primary text-center"><strong>การติดตามลูกหนี้ค่ารักษา ชําระเงิน OP</strong></div>          
            </div>            
        </div>  
        <div class="row">  
            @foreach($debtor as $row)          
            <div class="col-sm-6">                 
                <p class="text-primary">
                    ชื่อ-สกุล: <strong>{{$row->ptname}}</strong>              
                </p> 
                <p class="text-primary">
                    เลขบัตรประชาชน: <strong>{{$row->cid}}</strong> HN: <strong>{{$row->hn}}</strong>
                </p>   
                <p class="text-primary">
                    เบอร์โทร: <strong>{{$row->mobile_phone_number}}</strong>
                </p>   
            </div>   
            <div class="col-sm-6">                 
                <p class="text-primary">
                    วันที่รับบริการ: <strong>{{DateThai($row->vstdate)}}</strong> เวลา: <strong>{{$row->vsttime}}</strong>             
                </p> 
                <p class="text-primary">
                    สิทธิการรักษา: <strong>{{$row->pttype}}</strong> 
                </p>   
                <p class="text-primary">
                    ลูกหนี้ค่ารักษา: <strong>{{ number_format($row->debtor,2)}}</strong> บาท
                </p>   
            </div>   
            @endforeach          
        </div> 
        <hr>
    </div> <!-- row --> 

    <div class="container"> 
        <div class="row"  >            
            <div class="col-sm-6"> 
                <button type="button" class="btn btn-primary btn-sm text-white" data-toggle="modal" data-target="#insert-{{ $row->vn }}"> 
                    เพิ่มข้อมูล
                </button>     
            </div>       
            <div class="col-sm-6 text-danger" align="right"> 
                พิมพ์ใบแจ้งหนี้ที่ HOSxP
            </div>           
        </div>  
        <div style="overflow-x:auto;">
            <table class="table table-bordered table-striped my-3">
                <thead>
                <tr class="table-primary">
                    <th class="text-center">ครั้งที่</th>                    
                    <th class="text-center">วันที่ติดตาม</th>
                    <th class="text-center">การติดตาม</th> 
                    <th class="text-center">เลขที่เอกสาร</th> 
                    <th class="text-center">เจ้าหน้าที่ผู้ติดต่อ</th>                                       
                    <th class="text-center">หมายเหตุ</th>
                    <th class="text-center" width="6%">Action</th>                
                </thead>
                <?php $count = 1 ; ?>
                @foreach($tracking as $row)
                <tr>                                    
                    <td align="center">{{$count}}</td>
                    <td align="center">{{ DateThai($row->tracking_date) }}</td>
                    <td align="center">{{ $row->tracking_type }}</td>
                    <td align="center">{{ $row->tracking_no }}</td>   
                    <td align="left">{{ $row->tracking_officer }}</td>
                    <td align="left">{{ $row->tracking_note }}</td>  
                    <td align="center">        
                        <button type="button" class="btn btn-warning btn-sm text-primary " data-toggle="modal" data-target="#edit-{{ $row->tracking_id }}"> 
                        แก้ไข
                        </button>    
                    </td>                     
                <?php $count++; ?>                     
                @endforeach 
                </tr>   
            </table>
        </div> 
    </div> 

     <!-- Modal Structure insert -->
     @foreach($debtor as $row)
     <div id="insert-{{ $row->vn }}" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="insert-{{ $row->vn }}" aria-hidden="true">
         <div class="modal-dialog modal-lg">
         <div class="modal-content">
             <div class="modal-header">
             <h4 class="modal-title text-primary">รายละเอียดการติดตาม</h4>
             <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
             </button>
             </div>         
             <form action={{ url('hrims/debtor/1102050102_106/tracking_insert') }} method="POST" enctype="multipart/form-data">
                 @csrf
                 <div class="modal-body">
                     <input type="hidden" id="vn" name="vn" value="{{ $row->vn }}">                    
                     <div class="row">
                         <div class="col-md-6">  
                             <div class="mb-3">
                                 <label for="ptname" class="form-label">ชื่อ-สกุล : <strong><font style="color:blue">{{ $row->ptname }}</font></strong></label>           
                             </div>
                         </div>
                         <div class="col-md-6">  
                             <div class="mb-3">                          
                                 <label for="debtor" class="form-label">ลูกหนี้ : <strong><font style="color:blue">{{ $row->debtor }} </font> บาท</strong></label>           
                             </div>
                         </div>
                     </div>
                     <div class="row">
                         <div class="col-md-12">  
                             <div class="mb-3">
                                 <label for="item-description" class="form-label">วันที่ติดตาม : </label>
                                 <input type="date" class="form-control" id="tracking_date" name="tracking_date" >
                             </div>
                             <div class="mb-3">
                                <label for="item-description" class="form-label">การติดตาม : </label>
                                <select class="form-select my-1" name="tracking_type">                                                       
                                    <option value="โทรศัพท์">โทรศัพท์</option>                                           
                                    <option value="ส่งเอกสาร">ส่งเอกสาร</option> 
                                </select> 
                            </div>  
                             <div class="mb-3">
                                 <label for="item-description" class="form-label">เลขที่หนังสือ : </label>
                                 <input type="text" class="form-control" id="tracking_no" name="tracking_no">
                             </div>
                             <div class="mb-3">
                                 <label for="item-description" class="form-label">เจ้าหน้าที่ผู้ติดต่อ : </label>
                                 <input type="text" class="form-control" id="tracking_officer" name="tracking_officer">
                             </div> 
                             <div class="mb-3">
                                 <label for="item-description" class="form-label">หมายเหตุ : <strong><font style="color:blue"></font></strong></label>
                                 <input type="text" class="form-control" id="tracking_note" name="tracking_note">
                             </div>     
                         </div> 
                     </div> 
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                     <button type="submit" class="btn btn-success">บันทึกข้อมูล</button>
                 </div>
             </form>     
         </div>
         </div>
     </div>
    @endforeach
  
    <!-- Modal Structure edit -->
    @foreach($tracking as $row)
    <div id="edit-{{ $row->tracking_id }}" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="edit-{{ $row->tracking_id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="modal-title text-primary">รายละเอียดการติดตาม</h4>
            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </button>
            </div>         
            <form action={{ url('hrims/debtor/1102050102_106/tracking_update', $row->tracking_id) }} method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body"> 
                    <input type="hidden" id="tracking_id" name="tracking_id">    
                    <input type="hidden" id="vn" name="vn">                                    
                    <div class="row">
                        <div class="col-md-12">  
                            <div class="mb-3">
                                <label for="item-description" class="form-label">วันที่ติดตาม : <strong><font style="color:blue">{{ DateThai($row->tracking_date) }}</font></strong></label>
                                <input type="date" class="form-control" id="tracking_date" name="tracking_date" value="{{ $row->tracking_date }}" >
                            </div>
                            <div class="mb-3">
                                <label for="item-description" class="form-label">การติดตาม : <strong><font style="color:blue">{{ $row->tracking_type }}</font></strong></label>
                                <select class="form-select my-1" name="tracking_type">                                                       
                                    <option value="โทรศัพท์" @if ($row->tracking_type == 'โทรศัพท์') selected="selected" @endif>โทรศัพท์</option>                                           
                                    <option value="ส่งเอกสาร" @if ($row->tracking_type  == 'ส่งเอกสาร') selected="selected" @endif>ส่งเอกสาร</option> 
                                </select> 
                            </div> 
                            <div class="mb-3">
                                <label for="item-description" class="form-label">เลขที่หนังสือ : <strong><font style="color:blue">{{ $row->tracking_no }}</font></strong></label>
                                <input type="text" class="form-control" id="tracking_no" name="tracking_no" value="{{ $row->tracking_no }}" >
                            </div>
                            <div class="mb-3">
                                <label for="item-description" class="form-label">เจ้าหน้าที่ผู้ติดต่อ : <strong><font style="color:blue">{{ $row->tracking_officer }}</font></strong></label>
                                <input type="text" class="form-control" id="tracking_officer" name="tracking_officer" value="{{ $row->tracking_officer }}" >
                            </div>        
                            <div class="mb-3">
                                <label for="item-description" class="form-label">หมายเหตุ : <strong><font style="color:blue">{{ $row->tracking_note }}</font></strong></label>
                                <input type="text" style="height: 40px;" class="form-control" id="tracking_note" name="tracking_note" value="{{ $row->tracking_note }}" >
                            </div>     
                        </div> 
                    </div> 
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">บันทึกข้อมูล</button>
                </div>
            </form>     
        </div>
        </div>
    </div>
    @endforeach
    <br>
</body>

<!-- Modal -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>



