# คู่มือการปรับปรุงผังลูกหนี้สำหรับระบบยืนยัน (Batch Confirm) และระบบลบ (Batch Delete)

คู่มือนี้สรุปขั้นตอนและโค้ดต้นแบบสำหรับการปรับปรุงหน้าจอผังลูกหนี้ต่าง ๆ ในโปรเจกต์ `h-rims` ให้รองรับการประมวลผลเป็นกลุ่ม (Batch Processing) ผ่าน AJAX เพื่อป้องกันปัญหา 504 Gateway Timeout จากฐานข้อมูล HOSxP

---

## 1. การปรับปรุงฝั่ง Controller (`app/Http/Controllers/DebtorController.php`)

ให้ค้นหาฟังก์ชันการยืนยันและการลบของผังนั้น ๆ แล้วเพิ่มเงื่อนไขตรวจสอบ `$request->ajax()` เพื่อตอบกลับเป็น JSON

### A. ฟังก์ชัน Confirm (ยืนยัน)
เพิ่มการส่งกลับ JSON เมื่อส่งมาแบบ AJAX:
```php
        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ยืนยันลูกหนี้สำเร็จ'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
```

### B. ฟังก์ชัน Delete (ลบ)
ปรับเงื่อนไขการส่งกลับค่าผลลัพธ์การลบ (รวมถึงข้ามรายการที่ถูกล็อก) เป็นแบบ JSON:
```php
        if ($request->ajax()) {
            if ($locked_items == count($checkbox)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด',
                    'locked' => $locked_items,
                    'deleted' => 0
                ]);
            } elseif ($locked_items > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)",
                    'locked' => $locked_items,
                    'deleted' => count($deletable_items)
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'ลบลูกหนี้เรียบร้อย',
                'locked' => 0,
                'deleted' => count($deletable_items)
            ]);
        }
```

---

## 2. การปรับปรุงฝั่ง View (`resources/views/debtor/1102050101_XXX.blade.php`)

### A. ลบโค้ด Checkbox เก่าที่ส่วนหัวไฟล์
ลบแท็ก `<script>` สำหรับฟังก์ชัน `toggle` และ `toggle_d` (ที่มักอยู่บรรทัดแรก ๆ ของไฟล์) ออกทั้งหมด

### B. เพิ่มฟังก์ชันเลือกทั้งหมด (ALL) ในหน้าปัจจุบัน
นำโค้ดด้านล่างนี้ไปวางไว้ที่จุดเริ่มต้นของ `@push('scripts')`:
```javascript
window.toggle_d = function(source) {
    if ($.fn.DataTable.isDataTable('#debtor')) {
        let table = $('#debtor').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox_d[]"]').prop('checked', source.checked);
    } else {
        $('input[name="checkbox_d[]"]').prop('checked', source.checked);
    }
};

window.toggle = function(source) {
    if ($.fn.DataTable.isDataTable('#debtor_search')) {
        let table = $('#debtor_search').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox[]"]').prop('checked', source.checked);
    } else {
        $('input[name="checkbox[]"]').prop('checked', source.checked);
    }
};
```

### C. เพิ่มปุ่มตัวเลือกแสดงจำนวนแถว (200, 500, ทั้งหมด)
ค้นหาจุดประยุกต์ใช้ `.DataTable({...})` ของ `#debtor` และ `#debtor_search` แล้วเพิ่มตัวเลือก `lengthMenu`:
```javascript
lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
```

### D. ปรับปรุงการลบรายการ (Delete) ส่งข้อมูลทีละ 100 รายการพร้อม Progress Bar
แทนที่ฟังก์ชัน `confirmDelete()` เดิมด้วยโค้ดชุดนี้:
```javascript
    function confirmDelete() { 
        let selected = [];
        if ($.fn.DataTable.isDataTable('#debtor')) {
            let table = $('#debtor').DataTable();
            let cells = table.cells().nodes();
            $(cells).find('input[name="checkbox_d[]"]:checked').each(function() {
                selected.push($(this).val());
            });
        } else {
            selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);
        }

        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะลบ', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: `ต้องการลบลูกหนี้จำนวน ${selected.length} รายการที่เลือกใช่หรือไม่?`, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'ใช่, ลบเลย!', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const chunkSize = 100;
            const chunks = [];
            for (let i = 0; i < selected.length; i += chunkSize) {
                chunks.push(selected.slice(i, i + chunkSize));
            }
            
            let currentChunkIndex = 0;
            const total = selected.length;
            let totalDeleted = 0;
            let totalLocked = 0;
            
            Swal.fire({
                title: 'กำลังลบรายการลูกหนี้...',
                html: `
                    <div class="progress mb-2" style="height: 25px;">
                        <div id="delete-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="delete-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    sendNextDeleteChunk();
                }
            });
            
            function sendNextDeleteChunk() {
                if (currentChunkIndex >= chunks.length) {
                    let alertText = `ลบรายการลูกหนี้จำนวน ${totalDeleted} รายการเรียบร้อยแล้ว`;
                    if (totalLocked > 0) {
                        alertText += ` (ข้ามรายการที่ถูกล็อค ${totalLocked} รายการ)`;
                    }
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: alertText,
                        icon: totalLocked === total ? 'error' : (totalLocked > 0 ? 'warning' : 'success'),
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        location.reload();
                    });
                    return;
                }
                
                const chunk = chunks[currentChunkIndex];
                
                $.ajax({
                    url: "{{ url('debtor/1102050101_XXX_delete') }}", // เปลี่ยน XXX เป็นของผังนั้น ๆ
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE',
                        checkbox_d: chunk
                    },
                    success: function(res) {
                        currentChunkIndex++;
                        totalDeleted += (res.deleted || 0);
                        totalLocked += (res.locked || 0);
                        
                        const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                        const percent = Math.round((processedCount / total) * 100);
                        
                        const progressBar = document.getElementById('delete-progress-bar');
                        const progressText = document.getElementById('delete-progress-text');
                        if (progressBar) {
                            progressBar.style.width = percent + '%';
                            progressBar.setAttribute('aria-valuenow', percent);
                            progressBar.innerText = percent + '%';
                        }
                        if (progressText) {
                            progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                        }
                        
                        sendNextDeleteChunk();
                    },
                    error: function(xhr) {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบลูกหนี้บางรายการได้ กรุณาลองใหม่อีกครั้ง', 'error');
                    }
                });
            }
        } });
    }
```

### E. ปรับปรุงการยืนยันนำเข้า (Confirm) ส่งข้อมูลทีละ 10 รายการพร้อม Progress Bar
แทนที่ฟังก์ชัน `confirmSubmit()` เดิมด้วยโค้ดชุดนี้:
```javascript
    function confirmSubmit() {
        let selected = [];
        if ($.fn.DataTable.isDataTable('#debtor_search')) {
            let table = $('#debtor_search').DataTable();
            let cells = table.cells().nodes();
            $(cells).find('input[name="checkbox[]"]:checked').each(function() {
                selected.push($(this).val());
            });
        } else {
            selected = [...document.querySelectorAll('input[name="checkbox[]"]:checked')].map(e => e.value);
        }

        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: `ต้องการยืนยันลูกหนี้จำนวน ${selected.length} รายการที่เลือกใช่หรือไม่?`, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const chunkSize = 10;
            const chunks = [];
            for (let i = 0; i < selected.length; i += chunkSize) {
                chunks.push(selected.slice(i, i + chunkSize));
            }
            
            let currentChunkIndex = 0;
            const total = selected.length;
            
            Swal.fire({
                title: 'กำลังยืนยันลูกหนี้...',
                html: `
                    <div class="progress mb-2" style="height: 25px;">
                        <div id="confirm-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="confirm-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    sendNextChunk();
                }
            });
            
            function sendNextChunk() {
                if (currentChunkIndex >= chunks.length) {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: `ยืนยันลูกหนี้จำนวน ${total} เรียบร้อยแล้ว`,
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        location.reload();
                    });
                    return;
                }
                
                const chunk = chunks[currentChunkIndex];
                
                $.ajax({
                    url: "{{ url('debtor/1102050101_XXX_confirm') }}", // เปลี่ยน XXX เป็นของผังนั้น ๆ
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        checkbox: chunk
                    },
                    success: function(res) {
                        currentChunkIndex++;
                        const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                        const percent = Math.round((processedCount / total) * 100);
                        
                        const progressBar = document.getElementById('confirm-progress-bar');
                        const progressText = document.getElementById('confirm-progress-text');
                        if (progressBar) {
                            progressBar.style.width = percent + '%';
                            progressBar.setAttribute('aria-valuenow', percent);
                            progressBar.innerText = percent + '%';
                        }
                        if (progressText) {
                            progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                        }
                        
                        sendNextChunk();
                    },
                    error: function(xhr) {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถยืนยันลูกหนี้บางรายการได้ กรุณาลองใหม่อีกครั้ง', 'error');
            }
        } });
    }
```

### F. ปรับปรุงระยะห่างและไอคอนปุ่มยืนยันลูกหนี้
1. ในส่วน HTML ของแต่ละแท็บ (เช่น แท็บรอยืนยัน OPD/IPD) ให้ปรับระยะห่างของแถวปุ่มกับแท็บด้านบน โดยลบ class `mt-3` ออกจากแถวปุ่มเพื่อไม่ให้ระยะห่างมากเกินไป:
   ```html
   <!-- ก่อนปรับปรุง -->
   <div class="d-flex justify-content-between align-items-center mb-2 mt-3">

   <!-- หลังปรับปรุง (เหมือนกับผัง 201) -->
   <div class="d-flex justify-content-between align-items-center mb-2">
   ```
2. ปรับไอคอนและข้อความของปุ่มยืนยันลูกหนี้ให้เป็นรูปแบบมาตรฐาน:
   ```html
   <button type="button" class="btn btn-outline-success btn-sm" onclick="confirmSubmit()">
       <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
   </button>
   ```

### G. ปรับแต่ง Checkbox เลือกทั้งหมด (All) ให้มีขนาดพอดีและอยู่แนวเดียวกัน
1. ในส่วน `<th>` ของ Checkbox เลือกทั้งหมด ให้ปรับเป็น Flexbox และกำหนดความกว้างคงที่เพื่อป้องกันการตัดบรรทัดหรือซ้อนกัน:
   ```html
   <!-- ตัวอย่างตาราง debtor -->
   <th class="text-center" style="width: 70px; min-width: 70px; max-width: 70px;">
       <div class="d-flex align-items-center justify-content-center gap-1">
           <input type="checkbox" onClick="toggle_d(this)"> <span>All</span>
       </div>
   </th>
   ```
2. ปิดความสามารถในการจัดเรียง (Sorting) ของคอลัมน์แรกในค่าเริ่มต้นของ DataTable เพื่อซ่อนปุ่มลูกศรเรียงข้อมูลไม่ให้มาเบียด Checkbox โดยเพิ่ม `columnDefs` ในส่วนการประกาศ DataTable:
   ```javascript
   $('#debtor').DataTable({
       // ... ส่วนของการตั้งค่าอื่น ๆ ...
       columnDefs: [
           { orderable: false, targets: 0 }
       ]
   });
   ```


