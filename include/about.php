<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../assets/Css/styles.css">
</head>
<style>
    /* About Section Styles */
    .about {
        position: relative;
        overflow: hidden;
    }

    .section-title {
        text-align: center;
        margin-bottom: 70px;
        position: relative;
    }

    .section-title h2 {
        font-size: 42px;
        display: inline-block;
        position: relative;
        padding-bottom: 15px;
    }

    .section-title h2:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--primary);
    }

    .section-title p {
        max-width: 700px;
        margin: 20px auto 0;
        color: var(--gray);
        font-size: 18px;
    }

    .about-content {
        display: flex;
        align-items: center;
        gap: 60px;
    }

    .about-image {
        flex: 1;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        position: relative;
    }

    .about-image:before {
        content: '';
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        bottom: 20px;
        border: 2px solid var(--primary);
        border-radius: 15px;
        z-index: 2;
        pointer-events: none;
    }

    .about-image img {
        width: 100%;
        height: 600px;
        object-fit: cover;
        display: block;
        transition: transform 0.5s ease;
    }

    .about-image:hover img {
        transform: scale(1.05);
    }

    .about-text {
        flex: 1;
    }

    .about-text h3 {
        font-size: 32px;
        margin-bottom: 20px;
        color: var(--secondary);
    }

    .about-text p {
        margin-bottom: 20px;
        color: var(--dark);
        font-size: 17px;
        line-height: 1.8;
    }

    .about-features {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
        margin-top: 40px;
    }

    .feature-box {
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: var(--gold-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--primary);
        font-size: 24px;
    }

    .feature-content h4 {
        font-size: 20px;
        margin-bottom: 8px;
        color: var(--secondary);
    }

    .feature-content p {
        font-size: 15px;
        color: var(--gray);
        margin-bottom: 0;
    }

    .about-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-top: 70px;
        text-align: center;
    }

    /* Responsive Design */
    @media (max-width: 1100px) {
        .nav-links {
            gap: 20px;
        }

        .about-content {
            gap: 40px;
        }

        .about-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 992px) {
            .about-content {
                flex-direction: column;
            }

            .about-image,
            .about-text {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .about-stats {
                grid-template-columns: 1fr;
            }

            .about-features {
                grid-template-columns: 1fr;
            }

        }
    }
</style>

<body>
    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="section-title">
                <h2>About RoyalNest</h2>
                <p>Discover the story behind our legacy of luxury and unparalleled hospitality</p>
            </div>

            <div class="about-content">
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1561501900-3701fa6a0864?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80" alt="RoyalNest Resort">
                </div>

                <div class="about-text">
                    <h3>Where Luxury Meets Serenity</h3>
                    <p>Nestled amidst breathtaking natural landscapes, RoyalNest Resort has been the epitome of luxury hospitality since 1998. Founded by the visionary hotelier Robert Kingsley, our resort was conceived as a sanctuary where discerning travelers could experience the perfect harmony of opulence and tranquility.</p>

                    <p>With architecture inspired by royal palaces and interiors crafted by world-renowned designers, RoyalNest offers an unparalleled experience that transcends ordinary luxury. Our commitment to excellence has earned us the prestigious Global Luxury Award for five consecutive years.</p>

                    <div class="about-features">
                        <div class="feature-box">
                            <div class="feature-icon">
                                <i class="fas fa-spa"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Holistic Wellness</h4>
                                <p>Our award-winning spa offers treatments inspired by ancient healing traditions.</p>
                            </div>
                        </div>

                        <div class="feature-box">
                            <div class="feature-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Culinary Excellence</h4>
                                <p>Michelin-starred chefs create unforgettable dining experiences with locally-sourced ingredients.</p>
                            </div>
                        </div>

                        <div class="feature-box">
                            <div class="feature-icon">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Personalized Service</h4>
                                <p>Our dedicated staff provides bespoke services tailored to your every need.</p>
                            </div>
                        </div>

                        <div class="feature-box">
                            <div class="feature-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <div class="feature-content">
                                <h4>Sustainable Luxury</h4>
                                <p>We're committed to environmental stewardship through eco-friendly practices.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-stats">
                <div class="stat-box">
                    <div class="stat-number">25</div>
                    <div class="stat-title">Years of Excellence</div>
                </div>

                <div class="stat-box">
                    <div class="stat-number">120</div>
                    <div class="stat-title">Luxury Suites</div>
                </div>

                <div class="stat-box">
                    <div class="stat-number">7</div>
                    <div class="stat-title">Fine Dining Restaurants</div>
                </div>

                <div class="stat-box">
                    <div class="stat-number">98%</div>
                    <div class="stat-title">Guest Satisfaction</div>
                </div>
            </div>
        </div>
    </section>

</body>

</html>