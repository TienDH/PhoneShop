<footer class="tdh-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                @include('partials.tdh-logo', ['class' => 'tdh-logo mb-3'])
                <p class="mb-3" style="line-height: 1.8;">
                    TDH Phone mang đến điện thoại chính hãng, giá minh bạch và dịch vụ hậu mãi tận tâm cho mọi khách hàng.
                </p>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="btn btn-sm btn-outline-light rounded-circle" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Sản phẩm</h6>
                <a href="{{ route('front.home') }}#products">iPhone</a>
                <a href="{{ route('front.home') }}#products">Samsung</a>
                <a href="{{ route('front.home') }}#products">Xiaomi</a>
                <a href="{{ route('front.home') }}#products">Phụ kiện</a>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Chính sách</h6>
                <a href="#">Đổi trả hàng</a>
                <a href="#">Bảo hành</a>
                <a href="#">Thanh toán</a>
                <a href="#">Vận chuyển</a>
            </div>

            <div class="col-12 col-lg-4">
                <h6>Liên hệ</h6>
                <a href="tel:18001234"><i class="bi bi-telephone"></i>1800 1234 (miễn phí)</a>
                <a href="mailto:support@tdhphone.vn"><i class="bi bi-envelope"></i>support@tdhphone.vn</a>
                <a href="#"><i class="bi bi-geo-alt"></i>123 Nguyễn Văn A, Q.1, TP.HCM</a>
                <a href="#"><i class="bi bi-clock"></i>8:00 - 22:00 hằng ngày</a>
            </div>
        </div>

        <hr class="tdh-footer-divider my-4">

        <div class="d-flex justify-content-between flex-wrap gap-2">
            <small>&copy; {{ now()->year }} TDH Phone. All rights reserved.</small>
            <small>Thiết kế bởi TDH Phone Team</small>
        </div>
    </div>
</footer>
