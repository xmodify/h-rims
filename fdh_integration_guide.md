# คู่มือการเพิ่มปุ่มดึงข้อมูล FDH ในหน้าอื่น ๆ (FDH Integration Guide)

คู่มือนี้แนะนำขั้นตอนการเพิ่มปุ่ม **ดึง FDH** สำหรับหน้าเพจใหม่ ๆ ให้มีรูปแบบการทำงานและหน้าตาป๊อปอัปเหมือนกับหน้า [ucs_incup.blade.php](file:///d:/Project%20Laravel/h-rims/resources/views/claim_op/ucs_incup.blade.php)

---

## 1. การทำงานเบื้องหลัง (How it works)
ระบบทำงานด้วยการส่งรายการคนไข้เป็นกลุ่ม (Chunk ละ 50 รายการ) ไปตรวจสอบที่ API ฝั่ง Backend แล้วส่งสถานะกลับมาอัปเดตบน Progress Bar และ Log Box ของ SweetAlert2 แบบเรียลไทม์:
* **Backend API**: `/api/fdh/check-chunk` (ประมวลผลข้อมูลคนไข้แบบขนานด้วย `Http::pool` สูงสุดครั้งละ 50 รายการ)
* **JS Helper ส่วนกลาง**: ฟังก์ชัน `runFdhBulkCheck` ในไฟล์ [layouts/app.blade.php](file:///d:/Project%20Laravel/h-rims/resources/views/layouts/app.blade.php)

---

## 2. ขั้นตอนการติดตั้งในหน้าใหม่

### ขั้นตอนที่ 1: เพิ่มปุ่มในส่วน HTML (Blade View)
ให้เพิ่มปุ่มสำหรับกดดึงข้อมูล FDH ในตำแหน่งที่ต้องการ (โดยปกติจะอยู่ในฟอร์มค้นหาช่วงเวลา หรือกลุ่มปุ่มควบคุมหลัก):

```html
<button onclick="checkFdhBulk(event)" type="button" class="btn btn-info text-white px-3 shadow-sm" title="ดึงสถานะ FDH ตามช่วงเวลาที่เลือก">
    <i class="bi bi-arrow-repeat me-1"></i> ดึง FDH
</button>
```

---

### ขั้นตอนที่ 2: สร้างฟังก์ชัน `checkFdhBulk` ในส่วน JavaScript
เพิ่มฟังก์ชัน JavaScript ในหน้าเพจเพื่อเตรียมข้อมูลคนไข้ (HN, SEQ/AN) และเรียกใช้ฟังก์ชันส่วนกลาง `runFdhBulkCheck`:

#### ตัวอย่างแบบที่ 1: กรณีมีกลุ่มข้อมูลเดียว (เช่น ข้อมูลในตัวแปร `$search`)
```javascript
async function checkFdhBulk(e) {
    if (e) e.preventDefault();
    
    // 1. ดึงข้อมูลผู้ป่วยจากตัวแปร PHP/Blade ที่โหลดมาแสดงในหน้านั้น
    const items = {!! json_encode(array_map(function($row) {
        return [
            'hn'  => $row->hn,
            'seq' => $row->seq, // หรือส่งค่าอื่นตามโครงสร้างตาราง
            'an'  => ''
        ];
    }, $search)) !!};

    // 2. ตรวจสอบว่ามีข้อมูลหรือไม่
    if (!items || items.length === 0) {
        Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
        return;
    }

    // 3. เรียกฟังก์ชันส่วนกลางเพื่อดึงข้อมูล FDH
    await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
        // Callback เมื่อทำงานเสร็จสิ้น (เช่น โหลดหน้าเว็บใหม่ หรือ Submit ฟอร์มดึงข้อมูลตารางใหม่)
        fetchData();
        $('#form_indiv').submit();
    });
}
```

#### ตัวอย่างแบบที่ 2: กรณีมีข้อมูล 2 กลุ่มรวมกัน (เช่น รอส่ง `$search` และ ส่งแล้ว `$claim` เหมือนหน้า incup)
```javascript
async function checkFdhBulk(e) {
    if (e) e.preventDefault();
    
    const searchItems = {!! json_encode(array_map(function($row) {
        return [ 'hn' => $row->hn, 'seq' => $row->seq, 'an' => '' ];
    }, $search)) !!};

    const claimItems = {!! json_encode(array_map(function($row) {
        return [ 'hn' => $row->hn, 'seq' => $row->seq, 'an' => '' ];
    }, $claim)) !!};

    const items = [...searchItems, ...claimItems];

    if (!items || items.length === 0) {
        Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
        return;
    }

    await runFdhBulkCheck(items, "{{ csrf_token() }}", "{{ url('/api/fdh/check-chunk') }}", function() {
        localStorage.setItem('active_tab', '#search'); // บันทึก Tab ล่าสุด
        fetchData();
        $('#form_indiv').submit();
    });
}
```

---

## 3. พารามิเตอร์ของฟังก์ชัน `runFdhBulkCheck`
ฟังก์ชัน `runFdhBulkCheck` รับพารามิเตอร์ 4 ตัวตามลำดับดังนี้:
1. `items` *(Array)*: รายการคนไข้ที่ต้องการดึงข้อมูล โดยแต่ละรายการต้องเป็น Object ที่มีโครงสร้างดังนี้:
   ```json
   {
     "hn": "123456",
     "seq": "78910",
     "an": ""
   }
   ```
2. `csrfToken` *(String)*: โทเค็น CSRF ของ Laravel เพื่อความปลอดภัยในการยิง API (ส่งด้วย `"{{ csrf_token() }}"`)
3. `checkChunkUrl` *(String)*: URL ไปยัง Endpoint ของ API (ส่งด้วย `"{{ url('/api/fdh/check-chunk') }}"`)
4. `reloadCallback` *(Function)*: ฟังก์ชันที่จะให้ทำงานเมื่อทำงานสำเร็จครบ 100% (เช่น การสั่ง reload ตารางข้อมูล)
