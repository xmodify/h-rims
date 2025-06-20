@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <button id="gitPullBtn">Git Pull</button>

    <pre id="gitOutput" style="background: #eee; padding: 1rem; margin-top: 1rem;"></pre>

    <script>
        document.getElementById('gitPullBtn').addEventListener('click', function () {
            if (!confirm("คุณแน่ใจว่าจะ Git Pull ใช่ไหม?")) return;

            let outputBox = document.getElementById('gitOutput');
            outputBox.textContent = 'กำลังดำเนินการ...';

            fetch("{{ route('admin.git.pull') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
            })
            .then(response => response.json())
            .then(data => {
                outputBox.textContent = data.output || data.error || 'ไม่มีข้อมูล';
            })
            .catch(error => {
                outputBox.textContent = "เกิดข้อผิดพลาด: " + error;
            });
        });
    </script>
    
</div>
@endsection
