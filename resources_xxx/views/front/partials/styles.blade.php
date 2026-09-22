<style>
    :root {
        --primary-color: #11D66C;
        --secondary-color: #FFB443;
        --dark-color: #2F2E41;
        --light-color: #F8F9FA;
        --grey-color: #6C757D;
        --border-color: #E5E5E5;
    }
    
    body {
        font-family: 'Cairo', sans-serif;
        background-color: #FFFFFF;
        color: var(--dark-color);
        line-height: 1.6;
    }
    
    /* Header & Navbar */
    .navbar {
        background-color: #FFFFFF;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 15px 0;
        transition: all 0.3s ease;
    }
    
    .navbar.scrolled {
        padding: 10px 0;
    }
    
    .logo-img {
        height: 45px;
        margin-left: 10px;
    }
    
    .logo-text {
        font-size: 24px;
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
    }
    
    .nav-link {
        color: var(--dark-color);
        font-weight: 600;
        margin: 0 10px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .nav-link:hover {
        color: var(--primary-color);
    }
    
    .nav-link.active {
        color: var(--primary-color);
    }
    
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -5px;
        right: 0;
        width: 100%;
        height: 2px;
        background-color: var(--primary-color);
    }
    
    .btn-outline-primary {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transition: all 0.3s ease;
        font-weight: 600;
    }
    
    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }
    
    /* أنماط أيقونات الهيدر */
    .navbar .btn-link {
        color: var(--grey-color);
        font-size: 1.2rem;
        padding: 5px;
        margin: 0 5px;
        transition: all 0.3s ease;
    }
    
    .navbar .btn-link:hover {
        color: var(--primary-color);
    }
    
    /* Dropdown Styles */
    .dropdown-menu {
        border-radius: 6px;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 10px;
        min-width: 180px;
        right: 0;
        left: auto;
        margin-top: 10px;
    }
    
    .dropdown-item {
        padding: 8px 15px;
        color: var(--dark-color);
        font-weight: 500;
        border-radius: 4px;
        transition: all 0.2s ease;
        text-align: right;
    }
    
    .dropdown-item:hover, .dropdown-item:focus {
        background-color: rgba(255, 107, 53, 0.1);
        color: var(--primary-color);
    }
    
    /* Hero Section */
    .hero-section {
        position: relative;
        height: 80vh;
        display: flex;
        align-items: center;
        overflow: hidden;
        background-color: #FAFAFA;
    }
    
    .hero-content {
        position: relative;
        z-index: 10;
    }
    
    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
        color: var(--dark-color);
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        color: var(--grey-color);
    }
    
    .hero-image {
        max-width: 100%;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    /* Categories Section */
    .categories-section {
        padding: 5rem 0;
    }
    
    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: var(--dark-color);
        text-align: center;
    }
    
    .section-subtitle {
        font-size: 1.1rem;
        color: var(--grey-color);
        margin-bottom: 3rem;
        text-align: center;
    }
    
    .category-card {
        background-color: #FFFFFF;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .category-img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    
    .category-content {
        padding: 1.5rem;
    }
    
    .category-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .category-desc {
        color: var(--grey-color);
        margin-bottom: 1rem;
    }
    
    /* Popular Food Trucks */
    .food-trucks-section {
        padding: 5rem 0;
        background-color: #FAFAFA;
    }
    
    .truck-card {
        background-color: #FFFFFF;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .truck-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .truck-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    
    .truck-content {
        padding: 1.5rem;
    }
    
    .truck-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .truck-location {
        display: flex;
        align-items: center;
        color: var(--grey-color);
        margin-bottom: 1rem;
    }
    
    .truck-location i {
        margin-left: 0.5rem;
        color: var(--primary-color);
    }
    
    .truck-rating {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .truck-rating i {
        color: #FFD700;
        margin-left: 0.25rem;
    }
    
    .truck-category-tag {
        display: inline-block;
        background-color: var(--secondary-color);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    /* App Features */
    .features-section {
        padding: 5rem 0;
    }
    
    .feature-card {
        text-align: center;
        padding: 2rem;
        transition: all 0.3s ease;
    }
    
    .feature-icon {
        font-size: 3rem;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
    }
    
    .feature-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .feature-desc {
        color: var(--grey-color);
    }
    
    /* Call-to-Action */
    .cta-section {
        padding: 5rem 0;
        background-color: var(--primary-color);
        color: white;
        text-align: center;
    }
    
    .cta-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    
    .cta-subtitle {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    
    .btn-white {
        background-color: white;
        color: var(--primary-color);
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 30px;
        transition: all 0.3s ease;
    }
    
    .btn-white:hover {
        background-color: rgba(255,255,255,0.9);
        transform: translateY(-3px);
    }
    
    /* Footer Styles */
    footer {
        background-color: var(--dark-color);
        color: white;
        padding: 5rem 0 0;
    }
    
    .footer-logo-container {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .footer-logo {
        height: 50px;
        margin-left: 10px;
    }
    
    .footer-logo-text {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
    }
    
    .footer-description {
        color: rgba(255,255,255,0.7);
        margin-bottom: 1.5rem;
    }
    
    .social-links {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: rgba(255,255,255,0.1);
        border-radius: 50%;
        color: white;
        transition: all 0.3s ease;
    }
    
    .social-links a:hover {
        background-color: var(--primary-color);
        transform: translateY(-3px);
    }
    
    .footer-heading {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: white;
        position: relative;
        padding-bottom: 0.75rem;
    }
    
    .footer-heading::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 50px;
        height: 2px;
        background-color: var(--primary-color);
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 0.75rem;
    }
    
    .footer-links a {
        color: rgba(255,255,255,0.7);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        padding-right: 15px;
    }
    
    .footer-links a::before {
        content: '\f053';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        right: 0;
        color: var(--primary-color);
    }
    
    .footer-links a:hover {
        color: white;
    }
    
    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-contact li {
        margin-bottom: 0.75rem;
        color: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
    }
    
    .footer-contact li i {
        color: var(--primary-color);
        margin-left: 0.75rem;
        min-width: 20px;
    }
    
    .footer-bottom {
        background-color: rgba(0,0,0,0.2);
        text-align: center;
        padding: 1.5rem 0;
        margin-top: 4rem;
    }
    
    .footer-bottom p {
        margin: 0;
        color: rgba(255,255,255,0.7);
    }
    
    /* Buttons */
    .btn {
        font-weight: 600;
        padding: 0.5rem 1.5rem;
        border-radius: 5px;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
        background-color: #e55a29;
        border-color: #e55a29;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .hero-section {
            height: auto;
            padding: 6rem 0;
        }
        
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-subtitle {
            font-size: 1rem;
        }
        
        .section-title {
            font-size: 1.75rem;
        }
        
        .cta-title {
            font-size: 1.75rem;
        }
        
        .feature-icon {
            font-size: 2.5rem;
        }
        
        .feature-title {
            font-size: 1.25rem;
        }
    }
</style> 