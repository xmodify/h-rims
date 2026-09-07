<!-- Modal แสดงรายงานรายละเอียดสำหรับหน้า Home (Home Detail Modal) -->
<div class="modal fade" id="homeDetailModal" tabindex="-1" aria-labelledby="homeDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable home-detail-dialog">
    <div class="modal-content border-0 shadow-2xl home-detail-content">
      
      <!-- Modal Header: Title, Icon, Full-page Link, Reload, and Close -->
      <div class="modal-header bg-white border-bottom py-2.5 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2.5">
          <div id="homeDetailIconWrapper" class="rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px; background: rgba(59, 130, 246, 0.12); flex-shrink: 0;">
            <i id="homeDetailModalIcon" class="bi bi-file-earmark-medical fs-5 text-primary"></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h6 class="modal-title fw-bold mb-0 text-dark" id="homeDetailModalTitle" style="letter-spacing: -0.2px;">
                รายละเอียดรายงาน
              </h6>
              <span class="badge bg-light text-secondary border px-2 py-0.5 small" style="font-size: 0.7rem; font-weight: 500;">
                <i class="bi bi-window-dock me-1"></i>Modal View
              </span>
            </div>
            <div class="text-muted small mt-0.5 d-flex align-items-center gap-2" style="font-size: 0.78rem;">
              <span id="homeDetailModalSubtitle">หน้ารายงานและระบบจัดการข้อมูล RiMS</span>
            </div>
          </div>
        </div>

        <!-- Controls on the Right -->
        <div class="d-flex align-items-center gap-2 ms-auto">
          <!-- Refresh Iframe Button -->
          <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 shadow-sm text-nowrap fw-semibold" id="homeDetailReloadBtn" title="รีเฟรชหน้านี้">
            <i class="bi bi-arrow-clockwise"></i>
          </button>

          <!-- Close Modal Button -->
          <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Close" id="homeDetailCloseBtn"></button>
        </div>
      </div>

      <!-- Modal Body (Iframe + Loading state) -->
      <div class="modal-body p-0 position-relative flex-grow-1 bg-light" style="overflow: hidden;">
        
        <!-- Loading Overlay -->
        <div id="homeDetailLoading" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-white" style="z-index: 20; transition: opacity 0.25s ease;">
          <div class="spinner-grow text-primary mb-3" style="width: 2.75rem; height: 2.75rem;" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="fw-bold text-dark fs-6" id="homeDetailLoadingTitle">กำลังเปิดรายงาน...</div>
          <small class="text-muted mt-1" style="font-size: 0.8rem;">กำลังโหลดข้อมูลหน้ารายงาน กรุณารอสักครู่</small>
        </div>

        <!-- Embedded Iframe -->
        <iframe id="homeDetailIframe" 
                src="about:blank" 
                title="Home Detail Report"
                class="w-100 h-100" 
                style="border: none; min-height: 550px; display: block;" 
                onload="if (typeof onHomeDetailIframeLoaded === 'function') onHomeDetailIframeLoaded();">
        </iframe>

      </div>
    </div>
  </div>
</div>

<style>
  .home-detail-dialog {
    max-width: 96vw;
    width: 1540px;
    height: 92vh;
    margin: 2vh auto;
  }
  .home-detail-content {
    border-radius: 18px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  }
  @media (max-width: 768px) {
    .home-detail-dialog {
      max-width: 100vw;
      width: 100%;
      height: 100vh;
      margin: 0;
    }
    .home-detail-content {
      border-radius: 0;
      height: 100vh;
    }
  }
</style>

<script>
  function onHomeDetailIframeLoaded() {
    const iframe = document.getElementById('homeDetailIframe');
    const loading = document.getElementById('homeDetailLoading');
    if (!iframe || !loading) return;

    // Only hide if the iframe has a valid content URL loaded (not about:blank)
    try {
      if (iframe.contentWindow && iframe.contentWindow.location.href !== 'about:blank') {
        loading.style.opacity = '0';
        setTimeout(() => {
          loading.classList.add('d-none');
          loading.style.opacity = '1';
        }, 250);
      }
    } catch(e) {
      // Cross-origin fallback
      loading.style.opacity = '0';
      setTimeout(() => {
        loading.classList.add('d-none');
        loading.style.opacity = '1';
      }, 250);
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('homeDetailModal');
    if (!modalEl) return;

    // Reset iframe source on modal close to release memory & avoid stale view
    function resetHomeDetailModal() {
      const iframe = document.getElementById('homeDetailIframe');
      const loading = document.getElementById('homeDetailLoading');
      if (iframe) {
        iframe.src = 'about:blank';
      }
      if (loading) {
        loading.classList.remove('d-none');
        loading.style.opacity = '1';
      }
    }

    modalEl.addEventListener('hidden.bs.modal', resetHomeDetailModal);
    if (window.jQuery) {
      window.jQuery(modalEl).on('hidden.bs.modal', resetHomeDetailModal);
    }

    // Reload button handler
    const reloadBtn = document.getElementById('homeDetailReloadBtn');
    if (reloadBtn) {
      reloadBtn.addEventListener('click', function() {
        const iframe = document.getElementById('homeDetailIframe');
        const loading = document.getElementById('homeDetailLoading');
        if (iframe && iframe.src && iframe.src !== 'about:blank') {
          if (loading) {
            loading.classList.remove('d-none');
            loading.style.opacity = '1';
          }
          iframe.contentWindow.location.reload();
        }
      });
    }
  });
</script>
