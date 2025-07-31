<?php
require_once __DIR__ . '/../src/lib/database.php';
$db = Database::getConnection();

// Obtener algunos productos destacados (los 4 más recientes)
$productos_destacados = $db->query("SELECT id, nombre, precio, imagen FROM productos ORDER BY fecha_creacion DESC LIMIT 4");

include __DIR__ . '/../templates/partials/header.php';
?>
<style>
/* Estilos para el carrusel */
.carousel-container {
    position: relative;
    max-width: 100%;
    margin: auto;
    overflow: hidden;
    border-radius: 8px;
}
.carousel-slide {
    display: flex;
    transition: transform 0.5s ease-in-out;
}
.carousel-item {
    min-width: 100%;
    box-sizing: border-box;
}
.carousel-item img {
    width: 100%;
    height: 400px; /* Altura fija para el carrusel */
    object-fit: cover; /* Asegura que la imagen cubra el espacio sin deformarse */
    display: block;
}
.carousel-nav {
    position: absolute;
    top: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    transform: translateY(-50%);
}
.carousel-nav button {
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    font-size: 18px;
}

/* Estilos para la sección de destacados */
.featured-products {
    margin-top: 40px;
    text-align: center;
}
.featured-products h2 {
    color: #005A9C;
    margin-bottom: 30px;
}
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}
.product-card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    text-align: center;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.product-card img {
    max-width: 100%;
    height: 200px;
    object-fit: cover;
}
.product-card h3 {
    font-size: 1.2rem;
    margin: 10px 0;
}
.product-card .price {
    color: #007BFF;
    font-weight: bold;
    font-size: 1.1rem;
}
</style>

<!-- Carrusel de Fotos -->
<div class="carousel-container">
    <div class="carousel-slide">
        <div class="carousel-item"><img src="images/carousel1.jpg" alt="Impresora 3D en acción"></div>
        <div class="carousel-item"><img src="images/carousel2.jpg" alt="Modelo 3D detallado"></div>
        <div class="carousel-item"><img src="images/carousel3.jpg" alt="Variedad de filamentos"></div>
    </div>
    <div class="carousel-nav">
        <button id="prevBtn">‹</button>
        <button id="nextBtn">›</button>
    </div>
</div>

<div style="text-align: center; padding: 50px 0;">
    <h2>Tu Visión, Hecha Realidad</h2>
    <p>Ofrecemos servicios de impresión 3D de alta calidad para tus prototipos, piezas y modelos.</p>
    <a href="catalogo.php" class="btn" style="font-size: 1.2rem; padding: 15px 30px;">Ver Catálogo de Productos</a>
</div>

<!-- Productos Destacados -->
<div class="featured-products">
    <h2>Productos Destacados</h2>
    <div class="product-grid">
        <?php if ($productos_destacados->num_rows > 0):
            while ($producto = $productos_destacados->fetch_assoc()): ?>
            <div class="product-card">
                <a href="producto_detalle.php?id=<?php echo $producto['id']; ?>">
                    <img src="uploads/products/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                </a>
                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                <p class="price"><?php echo number_format($producto['precio'], 2); ?> €</p>
                <a href="carrito.php?action=add&id=<?php echo $producto['id']; ?>" class="btn">Añadir al Carrito</a>
            </div>
            <?php endwhile;
        else: ?>
            <p>No hay productos destacados en este momento.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const slide = document.querySelector('.carousel-slide');
    const items = document.querySelectorAll('.carousel-item');
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');

    let counter = 0;
    const size = items[0].clientWidth;

    function updateSlide() {
        slide.style.transform = 'translateX(' + (-size * counter) + 'px)';
    }

    nextBtn.addEventListener('click', () => {
        counter++;
        if (counter >= items.length) {
            counter = 0;
        }
        updateSlide();
    });

    prevBtn.addEventListener('click', () => {
        counter--;
        if (counter < 0) {
            counter = items.length - 1;
        }
        updateSlide();
    });

    // Auto-slide
    setInterval(() => {
        nextBtn.click();
    }, 5000);

    window.addEventListener('resize', updateSlide);
});
</script>

<?php include __DIR__ . '/../templates/partials/footer.php'; ?>
