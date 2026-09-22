<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="footer-logo-container">
                    <span class="footer-logo-text">عابر</span>
                </div>
                <p class="footer-description mt-3">
                    منصة عابر هي المنصة الأولى في المملكة العربية السعودية المتخصصة في عربات الطعام، حيث نقدم خدمات الحجز والتوصيل من مختلف عربات الطعام.
                </p>
                <div class="social-links">
                    <a href="#" title="فيسبوك"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="تويتر"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="انستغرام"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="سناب شات"><i class="fab fa-snapchat"></i></a>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5 class="footer-heading">روابط سريعة</h5>
                <ul class="footer-links">
                    <li><a href="#">الرئيسية</a></li>
                    <li><a href="#">عربات الطعام</a></li>
                    <li><a href="#">الأصناف</a></li>
                    <li><a href="#">عن عابر</a></li>
                    <li><a href="#">تواصل معنا</a></li>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5 class="footer-heading">أصحاب العربات</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('provider.login') }}">تسجيل الدخول</a></li>
                    <li><a href="{{ route('provider.phone') }}">تسجيل عربة جديدة</a></li>
                    <li><a href="#">كيف تعمل المنصة</a></li>
                    <li><a href="#">الشروط والأحكام</a></li>
                </ul>
            </div>
            
            <div class="col-md-2 mb-4">
                <h5 class="footer-heading">تواصل معنا</h5>
                <ul class="footer-contact">
                    <li><i class="fas fa-phone"></i> 920012345</li>
                    <li><i class="fas fa-envelope"></i> info@abeer.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> الرياض، المملكة العربية السعودية</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; {{ date('Y') }} عابر. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</footer> 