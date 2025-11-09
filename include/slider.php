<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoyalNest Luxury Resort</title>
    <link rel="stylesheet" href="../assets/Css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<style>
    .slide-1 {
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .slide-2 {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("https://images.unsplash.com/photo-1414235077428-338989a2e8c0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80");
    }

    .slide-3 {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("https://images.unsplash.com/photo-1540497077202-7c8a3999166f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80");
    }

    .slide-4 {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("https://images.unsplash.com/photo-1551024506-0bccd828d307?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80");
    }

    .slide-5 {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80");
    }

    .slide-6 {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
            url("https://images.unsplash.com/photo-1617196034796-73dfa7b1fd56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1200&q=80");
    }
</style>

<body>
    <div class="container">
        <div class="slider-container">
            <div class="slider" id="slider">
                <div class="slide slide-1 active">
                    <div class="slide-content">
                        <h2>Luxury Resort Experience</h2>
                        <p>Discover unparalleled luxury at our premium resort with breathtaking views and world-class amenities</p>
                        <a href="#" class="slide-btn">Book Your Stay</a>
                    </div>
                </div>

                <div class="slide slide-2">
                    <div class="slide-content">
                        <h2>Fine Dining Excellence</h2>
                        <p>Indulge in gourmet cuisine crafted by award-winning chefs using the finest local ingredients</p>
                        <a href="#" class="slide-btn">View Our Menu</a>
                    </div>
                </div>

                <div class="slide slide-3">
                    <div class="slide-content">
                        <h2>Spa & Wellness Retreat</h2>
                        <p>Rejuvenate your mind and body with our signature treatments and holistic wellness programs</p>
                        <a href="#" class="slide-btn">Explore Treatments</a>
                    </div>
                </div>

                <div class="slide slide-4">
                    <div class="slide-content">
                        <span class="food-tag">Dessert</span>
                        <span class="food-tag">French</span>
                        <h2>Berry Pavlova</h2>
                        <p>Crispy meringue topped with vanilla cream and fresh seasonal berries</p>
                        <a href="#" class="slide-btn">View Recipe</a>
                    </div>
                </div>

                <div class="slide slide-5">
                    <div class="slide-content">
                        <h2>Adventure Activities</h2>
                        <p>Experience thrilling adventures with our curated selection of outdoor activities and excursions</p>
                        <a href="#" class="slide-btn">Discover Adventures</a>
                    </div>
                </div>

                <div class="slide slide-6">
                    <div class="slide-content">
                        <span class="food-tag">Japanese</span>
                        <span class="food-tag">Sushi</span>
                        <h2>Premium Sashimi Platter</h2>
                        <p>Assortment of the freshest tuna, salmon, and yellowtail with wasabi and ginger</p>
                        <a href="#" class="slide-btn">View Recipe</a>
                    </div>
                </div>
            </div>

            <div class="slider-controls">
                <div class="slider-btn" id="prev-btn">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="slider-btn" id="next-btn">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>

            <div class="slider-nav" id="slider-nav">
                <div class="nav-dot active" data-slide="0"></div>
                <div class="nav-dot" data-slide="1"></div>
                <div class="nav-dot" data-slide="2"></div>
                <div class="nav-dot" data-slide="3"></div>
                <div class="nav-dot" data-slide="4"></div>
                <div class="nav-dot" data-slide="5"></div>
            </div>
        </div>
    </div>
    <script src="../assets/Js/slider.js"></script>
</body>

</html>