// NSBM Marketplace Static Products & Video Packs Data

const INITIAL_PRODUCTS = [
    {
        id: 1,
        seller_id: 2,
        seller_name: "john",
        seller_email: "john@nsbm.ac.lk",
        category_id: "1",
        category_name: "Academic Resources",
        department: "Engineering",
        university: "General",
        title: "Engineering Mathematics II Textbook",
        description: "Gently used Engineering Mathematics II textbook. Essential for first-year computing and engineering students. No highlighting or ripped pages.",
        price: 1500.00,
        image_path: "assets/uploads/math_textbook.png",
        video_url: null,
        status: "approved"
    },
    {
        id: 2,
        seller_id: 2,
        seller_name: "john",
        seller_email: "john@nsbm.ac.lk",
        category_id: "2",
        category_name: "Electronics & Gadgets",
        department: "General",
        university: "General",
        title: "Casio fx-991ES Plus Scientific Calculator",
        description: "Fully functional scientific calculator. Perfect for engineering, computing, and business students. Comes with sliding cover.",
        price: 2500.00,
        image_path: "assets/uploads/calculator.png",
        video_url: null,
        status: "approved"
    },
    {
        id: 3,
        seller_id: 3,
        seller_name: "jane",
        seller_email: "jane@nsbm.ac.lk",
        category_id: "3",
        category_name: "Food & Beverages",
        department: "General",
        university: "General",
        title: "Freshly Baked Chocolate Chip Cookies",
        description: "Delicious homemade chocolate chip cookies, baked fresh daily. Box of 6 cookies. Order at least 1 day in advance!",
        price: 600.00,
        image_path: "assets/uploads/cookies.png",
        video_url: null,
        status: "approved"
    },
    {
        id: 4,
        seller_id: 3,
        seller_name: "jane",
        seller_email: "jane@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Computing",
        university: "General",
        title: "Python & Java Programming Tutoring",
        description: "One-on-one tutoring sessions for object-oriented programming. Perfect for students struggling with OOP or data structures courses. Rate per hour.",
        price: 1200.00,
        image_path: "assets/uploads/tutoring.png",
        video_url: null,
        status: "approved"
    },
    {
        id: 5,
        seller_id: 2,
        seller_name: "john",
        seller_email: "john@nsbm.ac.lk",
        category_id: "4",
        category_name: "Fashion & Accessories",
        department: "General",
        university: "General",
        title: "Custom NSBM Tote Bag - Eco Friendly",
        description: "Durable, hand-painted eco-friendly canvas tote bag with custom NSBM motifs. Excellent for carrying notebooks and tablets.",
        price: 850.00,
        image_path: "assets/uploads/totebag.png",
        video_url: null,
        status: "approved"
    },
    {
        id: 6,
        seller_id: 2,
        seller_name: "john",
        seller_email: "john@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Computing",
        university: "Plymouth",
        title: "Python OOP & Algorithms Video Masterclass - Plymouth Computing",
        description: "15-hour high definition video lecture pack covering Plymouth Software Engineering modules: Object Oriented Programming, Data Structures, and past paper walkthroughs.",
        price: 1800.00,
        image_path: "assets/uploads/computing_video.png",
        video_url: "https://www.youtube.com/embed/gfkTfcpWqAY",
        lesson_playlist: [
            { title: "Lesson 1: Introduction to OOP & Python Classes", url: "https://www.youtube.com/embed/gfkTfcpWqAY", duration: "45 mins" },
            { title: "Lesson 2: Inheritance, Polymorphism & Design Patterns", url: "https://www.youtube.com/embed/HXV3zeQKqGY", duration: "1 hr 10 mins" },
            { title: "Lesson 3: Plymouth Past Exam Solution Walkthrough", url: "https://www.youtube.com/embed/1v0mK5Z4_5M", duration: "55 mins" }
        ],
        status: "approved"
    },
    {
        id: 7,
        seller_id: 3,
        seller_name: "jane",
        seller_email: "jane@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Computing",
        university: "UGC/VU",
        title: "Database Systems & SQL Optimization Video Pack - UGC/VU Computing",
        description: "Complete video series explaining ER Diagram design, relational database normalization, SQL queries, and VU lab exam step-by-step guides.",
        price: 1600.00,
        image_path: "assets/uploads/computing_video.png",
        video_url: "https://www.youtube.com/embed/HXV3zeQKqGY",
        lesson_playlist: [
            { title: "Lesson 1: ER Diagrams & Relational Mapping", url: "https://www.youtube.com/embed/HXV3zeQKqGY", duration: "50 mins" },
            { title: "Lesson 2: Complex SQL Queries & Index Optimization", url: "https://www.youtube.com/embed/gfkTfcpWqAY", duration: "1 hr 05 mins" },
            { title: "Lesson 3: VU Database Lab Exam Walkthrough", url: "https://www.youtube.com/embed/n3E937xvv3g", duration: "40 mins" }
        ],
        status: "approved"
    },
    {
        id: 8,
        seller_id: 2,
        seller_name: "john",
        seller_email: "john@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Engineering",
        university: "Plymouth",
        title: "Engineering Statics & Dynamics Video Tutorials - Plymouth Engineering",
        description: "12 video modules covering Plymouth Mechanical & Civil Engineering mechanics, vector statics, stress analysis, and exam calculation tricks.",
        price: 2200.00,
        image_path: "assets/uploads/engineering_video.png",
        video_url: "https://www.youtube.com/embed/1v0mK5Z4_5M",
        lesson_playlist: [
            { title: "Lesson 1: Vector Mechanics & Equilibrium", url: "https://www.youtube.com/embed/1v0mK5Z4_5M", duration: "1 hr 15 mins" },
            { title: "Lesson 2: Truss Analysis & Internal Forces", url: "https://www.youtube.com/embed/n3E937xvv3g", duration: "50 mins" },
            { title: "Lesson 3: Plymouth Past Exam Calculation Review", url: "https://www.youtube.com/embed/yW_R9QWvY3U", duration: "45 mins" }
        ],
        status: "approved"
    },
    {
        id: 9,
        seller_id: 3,
        seller_name: "jane",
        seller_email: "jane@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Engineering",
        university: "UGC/VU",
        title: "Electrical Circuit Analysis & Microcontrollers Video Series - UGC/VU Engineering",
        description: "Comprehensive video guide for UGC/VU Mechatronics & Electrical Engineering students. Covers AC/DC circuits, Kirchhoff laws, and Arduino lab walkthroughs.",
        price: 2000.00,
        image_path: "assets/uploads/engineering_video.png",
        video_url: "https://www.youtube.com/embed/n3E937xvv3g",
        lesson_playlist: [
            { title: "Lesson 1: Kirchhoff Laws & Nodal Analysis", url: "https://www.youtube.com/embed/n3E937xvv3g", duration: "40 mins" },
            { title: "Lesson 2: AC Circuit Impedance & Phasors", url: "https://www.youtube.com/embed/1v0mK5Z4_5M", duration: "55 mins" },
            { title: "Lesson 3: Arduino Microcontroller Lab Guide", url: "https://www.youtube.com/embed/nU2T1QZ3tG8", duration: "1 hr 00 mins" }
        ],
        status: "approved"
    },
    {
        id: 10,
        seller_id: 3,
        seller_name: "jane",
        seller_email: "jane@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Business",
        university: "Plymouth",
        title: "Financial Accounting & Managerial Analytics Video Pack - Plymouth Business",
        description: "Plymouth Business School complete video course covering financial statement analysis, balance sheets, cash flow forecasting, and Excel modeling.",
        price: 1750.00,
        image_path: "assets/uploads/business_video.png",
        video_url: "https://www.youtube.com/embed/yW_R9QWvY3U",
        lesson_playlist: [
            { title: "Lesson 1: Income Statements & Balance Sheets", url: "https://www.youtube.com/embed/yW_R9QWvY3U", duration: "50 mins" },
            { title: "Lesson 2: Cash Flow Forecasting & Excel Ratios", url: "https://www.youtube.com/embed/nU2T1QZ3tG8", duration: "1 hr 10 mins" },
            { title: "Lesson 3: Plymouth Business Analytics Exam Prep", url: "https://www.youtube.com/embed/gfkTfcpWqAY", duration: "45 mins" }
        ],
        status: "approved"
    },
    {
        id: 11,
        seller_id: 2,
        seller_name: "john",
        seller_email: "john@nsbm.ac.lk",
        category_id: "5",
        category_name: "Student Services",
        department: "Business",
        university: "UGC/VU",
        title: "Principles of Marketing & Strategic Management Video Series - UGC/VU Business",
        description: "UGC/VU Business Administration video revision pack including market segmentation strategies, SWOT analysis, consumer behavior, and case study solutions.",
        price: 1500.00,
        image_path: "assets/uploads/business_video.png",
        video_url: "https://www.youtube.com/embed/nU2T1QZ3tG8",
        lesson_playlist: [
            { title: "Lesson 1: Market Segmentation & 4Ps Strategy", url: "https://www.youtube.com/embed/nU2T1QZ3tG8", duration: "45 mins" },
            { title: "Lesson 2: Consumer Behavior & SWOT Matrix", url: "https://www.youtube.com/embed/yW_R9QWvY3U", duration: "50 mins" },
            { title: "Lesson 3: UGC/VU Strategic Management Case Study", url: "https://www.youtube.com/embed/HXV3zeQKqGY", duration: "1 hr 00 mins" }
        ],
        status: "approved"
    }
];

// Helper to get products from localStorage or fallback to defaults
function getProducts() {
    const saved = localStorage.getItem('nsbm_products');
    if (saved) {
        try { return JSON.parse(saved); } catch(e) {}
    }
    localStorage.setItem('nsbm_products', JSON.stringify(INITIAL_PRODUCTS));
    return INITIAL_PRODUCTS;
}

// Helper to get shopping cart
function getCart() {
    const saved = localStorage.getItem('nsbm_cart');
    if (saved) {
        try { return JSON.parse(saved); } catch(e) {}
    }
    return [];
}

// Helper to update cart badge count
function updateCartBadge() {
    const cart = getCart();
    const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const badge = document.getElementById('cart-badge-count');
    if (badge) badge.textContent = totalCount;
}

// Helper to add item to cart
function addToCart(productId) {
    const products = getProducts();
    const product = products.find(p => p.id == productId);
    if (!product) return;

    let cart = getCart();
    const existing = cart.find(item => item.product_id == productId);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            product_id: product.id,
            title: product.title,
            price: product.price,
            image_path: product.image_path,
            video_url: product.video_url,
            seller_name: product.seller_name,
            quantity: 1
        });
    }

    localStorage.setItem('nsbm_cart', JSON.stringify(cart));
    updateCartBadge();
    alert(`🛒 Added "${product.title}" to cart!`);
}
