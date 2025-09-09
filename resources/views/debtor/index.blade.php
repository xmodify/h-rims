@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    <div class="card-body">
    <div class="alert alert-success text-primary" role="alert"><strong>ลูกหนี้ค่ารักษาพยาบาล</strong></div>

      <div class="row">            
        <div class="col-md-12">
          <a class="btn btn-outline-danger" href="{{ url('debtor/check_income') }}" target="_blank">Check HOSxP</a> 
          <a class="btn btn-outline-success" href="{{ url('debtor/summary') }}" target="_blank">สรุปบัญชีลูกหนี้ค่ารักษาพยาบาลแยกตามผังบัญชี</a>  
        </div>  
      </div>
      <br>
      <!-- Row -->
      <div class="row justify-content-center">
        <div class="col-md-6">
          <table class="table table-hover">
            <thead>
              <tr class="table-primary">
                  <th class="text-left text-primary">ผู้ป่วยนอก</th>            
              </tr>  
            </thead> 
            <tbody>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_103') }}" target="_blank"><li>1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ</li></a></td>
              </tr> 
              <tr>
                <td><a href="{{ url('debtor/1102050101_109') }}" target="_blank"><li>1102050101.109-ลูกหนี้-ระบบปฏิบัติการฉุกเฉิน</li></a></td>
              </tr> 
              <tr>
                <td><a href="{{ url('debtor/1102050101_201') }}" target="_blank"><li>1102050101.201-ลูกหนี้ค่ารักษา UC-OP ใน CUP</li></a></td>
              </tr> 
              <tr>
                <td><a href="{{ url('debtor/1102050101_203') }}" target="_blank"><li>1102050101.203-ลูกหนี้ค่ารักษา UC-OP นอก CUP (ในจังหวัดสังกัด สธ.)</li></a></td>
              </tr> 
              <tr>
                <td class="text-danger"><li>1102050101.204-ลูกหนี้ค่ารักษา UC-OP นอก CUP (ต่างจังหวัดสังกัด สธ.)</li></td>
              </tr>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_209') }}" target="_blank"><li>1102050101.209-ลูกหนี้ค่ารักษา ด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)</li></a></td>
              </tr>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_216') }}" target="_blank"><li>1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)</li></a></td>
              </tr>   
              <tr>
                <td class="text-danger"><li>1102050101.222-ลูกหนี้ค่ารักษา OP-Refer</li></td>
              </tr>  
              <tr>
                <td><a href="{{ url('debtor/1102050101_301') }}" target="_blank"><li>1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย</li></a></td>
              </tr>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_303') }}" target="_blank"><li>1102050101.303-ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย สังกัด สป.สธ.</li></a></td>
              </tr>      
              <tr>
                <td><a href="{{ url('debtor/1102050101_307') }}" target="_blank"><li>1102050101.307-ลูกหนี้ค่ารักษา ประกันสังคม-กองทุนทดแทน</li></a></td>
              </tr>  
              <tr>
                <td><a href="{{ url('debtor/1102050101_309') }}" target="_blank"><li>1102050101.309-ลูกหนี้ค่ารักษา ประกันสังคม-ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP</li></a></td>
              </tr>    
              <tr>
                <td><a href="{{ url('debtor/1102050101_401') }}" target="_blank"><li>1102050101.401-ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง OP</li></a></td>
              </tr>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_501') }}" target="_blank"><li>1102050101.501-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP</li></a></td>
              </tr>     
              <tr>
                <td><a href="{{ url('debtor/1102050101_503') }}" target="_blank"><li>1102050101.503-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP</li></a></td>
              </tr>    
              <tr>
                <td class="text-danger"><li>1102050101.505-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว เบิกจากส่วนกลาง OP</li></td>
              </tr>    
              <tr>
                <td><a href="{{ url('debtor/1102050101_701') }}" target="_blank"><li>1102050101.701-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ OP ใน CUP</li></a></td>
              </tr>    
              <tr>
                <td><a href="{{ url('debtor/1102050101_702') }}" target="_blank"><li>1102050101.702-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ OP นอก CUP</li></a></td>
              </tr>   
              <tr>
                <td class="text-danger"><li>1102050101.703-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ เบิกจากส่วนกลาง OP</li></td>
              </tr> 
              <tr>
                <td><a href="{{ url('debtor/1102050102_106') }}" target="_blank"><li>1102050102.106-ลูกหนี้ค่ารักษา ชําระเงิน OP</li></a></td>
              </tr>   
              <tr>
                <td><a href="{{ url('debtor/1102050102_108') }}" target="_blank"><li>1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP</li></a></td>
              </tr>      
              <tr>
                <td class="text-danger"><li>1102050102.110-ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP</li></td>
              </tr>     
              <tr>
                <td class="text-danger"><li>1102050102.201-ลูกหนี้ค่ารักษา UC-OP นอกสังกัด สธ.</li></td>
              </tr>   
              <tr>
                <td class="text-danger"><li>1102050102.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย ต่างสังกัด สป.สธ.</li></td>
              </tr>       
              <tr>
                <td><a href="{{ url('debtor/1102050102_602') }}" target="_blank"><li>1102050102.602-ลูกหนี้ค่ารักษา พรบ.รถ OP</li></a></td>
              </tr>     
              <tr>
                <td><a href="{{ url('debtor/1102050102_801') }}" target="_blank"><li>1102050102.801-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.OP</li></a></td>
              </tr>    
              <tr>
                <td><a href="{{ url('debtor/1102050102_803') }}" target="_blank"><li>1102050102.803-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ OP</li></a></td>
              </tr>                                                                  
            </tbody>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-hover">
            <thead>
              <tr class="table-primary">
                  <th class="text-left text-primary">ผู้ป่วยใน</th>            
              </tr>  
            </thead> 
            <tbody>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_202') }}" target="_blank"><li>1102050101.202-ลูกหนี้ค่ารักษา UC-IP</li></a></td>
              </tr>       
              <tr>
                <td><a href="{{ url('debtor/1102050101_217') }}" target="_blank"><li>1102050101.217-ลูกหนี้ค่ารักษา UC-IP บริการเฉพาะ (CR)</li></a></td>
              </tr>   
              <tr>
                <td ><a href="{{ url('debtor/1102050101_302') }}" target="_blank"><li>1102050101.302-ลูกหนี้ค่ารักษา ประกันสังคม IP เครือข่าย</li></a></td>
              </tr>  
              <tr>
                <td><a href="{{ url('debtor/1102050101_304') }}" target="_blank"><li>1102050101.304-ลูกหนี้ค่ารักษา ประกันสังคม IP นอกเครือข่าย สังกัด สป.สธ.</li></a></td>
              </tr>         
              <tr>
                <td><a href="{{ url('debtor/1102050101_308') }}" target="_blank"><li>1102050101.308-ลูกหนี้ค่ารักษา ประกันสังคม 72 ชั่วโมงแรก</li></a></td>
              </tr>     
              <tr>
                <td><a href="{{ url('debtor/1102050101_310') }}" target="_blank"><li>1102050101.310-ลูกหนี้ค่ารักษา ประกันสังคม ค่าใช้จ่ายสูง IP</li></a></td>
              </tr>     
              <tr>
                <td><a href="{{ url('debtor/1102050101_402') }}" target="_blank"><li>1102050101.402-ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP</li></a></td>
              </tr>      
              <tr>
                <td><a href="{{ url('debtor/1102050101_502') }}" target="_blank"><li>1102050101.502-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว IP</li></a></td>
              </tr>   
              <tr>
                <td><a href="{{ url('debtor/1102050101_504') }}" target="_blank"><li>1102050101.504-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว IP นอก CUP</li></a></td>
              </tr>                  
              <tr>
                <td class="text-danger"><li>1102050101.506-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าวเบิกจากส่วนกลาง IP</li></td>
              </tr>    
              <tr>
                <td><a href="{{ url('debtor/1102050101_704') }}" target="_blank"><li>1102050101.704-ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะและสิทธิ เบิกจากส่วนกลาง IP</li></a></td>
              </tr>      
              <tr>
                <td><a href="{{ url('debtor/1102050102_107') }}" target="_blank"><li>1102050102.107-ลูกหนี้ค่ารักษา ชําระเงิน IP</li></a></td>
              </tr>        
              <tr>
                <td><a href="{{ url('debtor/1102050102_109') }}" target="_blank"><li>1102050102.109-ลูกหนี้ค่ารักษา เบิกต้นสังกัด IP</li></a></td>
              </tr>        
              <tr>
                <td class="text-danger"><li>1102050102.111-ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น IP</li></td>
              </tr>  
              <tr>
                <td class="text-danger"><li>1102050102.302-ลูกหนี้ค่ารักษา ประกันสังคม IP-นอกเครือข่าย ต่างสังกัด สป.สธ.</li></td>
              </tr>           
              <tr>
                <td><a href="{{ url('debtor/1102050102_603') }}" target="_blank"><li>1102050102.603-ลูกหนี้ค่ารักษา พรบ.รถ IP</li></a></td>
              </tr>  
              <tr>
                <td><a href="{{ url('debtor/1102050102_802') }}" target="_blank"><li>1102050102.802-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.IP</li></a></td>
              </tr>     
              <tr>
                <td><a href="{{ url('debtor/1102050102_804') }}" target="_blank"><li>1102050102.804-ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท.รูปแบบพิเศษ IP</li></a></td>
              </tr>                                           
            </tbody>
          </table>
        </div>
      </div>
      <!-- Row -->
    </div> 
  </div>
@endsection
