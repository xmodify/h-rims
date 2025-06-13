@extends('layouts.app')
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

@section('content')

<div class="container-fluid">       
  
    <form action="{{ url('import/stm_ucs_save') }}" method="POST" enctype="multipart/form-data">
        @csrf      
        <div class="row mb-2">            
            <div class="col"></div>
                <div class="col-md-5">
                    <div class="mb-3 mt-3">
                    <input class="form-control form-control-lg" id="formFileLg" name="file"
                        type="file" required>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                </div>
                </div>
            <div class="col"></div>
        </div>
        <div class="row mb-2">            
            <div align="center">
                <button type="submit"
                    class="mb-3 me-2 btn-icon btn-shadow btn-dashed btn btn-outline-primary">
                    <i class="fa-solid fa-cloud-arrow-up me-2" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="UP STM"></i>
                    UPLOAD STM
                </button> 
                @if ($message = Session::get('success'))
                <div class="text-success text-center"><strong>Import File {{ $message }} Success</strong></div>
                @endif 
            </div>
        </div>
    </form>
    <div class="alert alert-success text-primary" role="alert"><strong>ข้อมูล Statement ประกันสุขภาพ UCS [OP-IP]</strong></div> 

    <div class="card-body">
        <div style="overflow-x:auto;">   
            <table id="stm_ucs" class="table table-bordered table-striped my-3">
                <thead>
                    <tr class="table-primary">
                        <th class="text-center">Dep</th>
                        <th class="text-center">Filename</th> 
                        <th class="text-center">จำนวน REP</th> 
                        <th class="text-center">จำนวนราย</th>
                        <th class="text-center">เรียกเก็บ</th>                                     
                        <th class="text-center">ชดเชยสุทธิ</th>   
                    </tr>     
                    </thead> 
                    <?php $count = 1 ; ?>  
                    @foreach($stm_ucs as $row) 
                    <tr>
                        <td align="center">{{ $row->dep }}</td> 
                        <td align="right">{{ $row->stm_filename }}</td>
                        <td align="right">{{ $row->repno }}</td>                            
                        <td align="right">{{ number_format($row->count_cid) }}</td>                                   
                        <td align="right">{{ number_format($row->charge,2) }}</td>                                     
                        <td align="right">{{ number_format($row->receive_total,2) }}</td>  
                    </tr>                
                    <?php $count++; ?>  
                    @endforeach   
            </table>
        </div> 
    </div>                
 
</div> 
@endsection
<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#stm_ucs').DataTable();
    });
</script>
<script type="text/javascript" class="init">
    $(document).ready(function () {
        $('#stm_ucs_list').DataTable();
    });
</script>