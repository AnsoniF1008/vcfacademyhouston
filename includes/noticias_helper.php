<?php
/**
 * includes/noticias_helper.php
 *
 * Funciones reutilizables para el sistema de noticias.
 * Incluir donde se necesite trabajar con noticias.
 *
 * Uso:
 *   require_once __DIR__ . '/noticias_helper.php';
 *   $ultimas = vcf_noticias_ultimas($pdo, 3);
 */

if (!function_exists('vcf_slug')) {
    /**
     * Convierte un título en slug URL-friendly.
     * "Match Recap vs Dynamo!" → "match-recap-vs-dynamo"
     */
    function vcf_slug(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        // Quitar acentos
        $texto = strtr($texto,
            'áàäâãéèëêíìïîóòöôõúùüûñç',
            'aaaaaeeeeiiiiooooouuuunc'
        );
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');
        return $texto ?: 'noticia';
    }
}

if (!function_exists('vcf_noticias_ultimas')) {
    /**
     * Obtiene las N noticias más recientes publicadas.
     *
     * @param PDO $pdo
     * @param int $limit         Cantidad a traer (default 3)
     * @param int|null $categoriaId Filtrar por categoría (opcional)
     * @param int|null $excluirId   ID a excluir (útil en página detalle)
     */
    function vcf_noticias_ultimas(PDO $pdo, int $limit = 3, ?int $categoriaId = null, ?int $excluirId = null): array
    {
        try {
            $sql = "
                SELECT n.id, n.titulo, n.slug, n.resumen, n.imagen_destacada, n.imagen_alt,
                       n.fecha_publicacion, n.autor, n.views,
                       c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.color AS categoria_color
                FROM noticias n
                LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
                WHERE n.publicado = 1
                  AND (n.fecha_publicacion IS NULL OR n.fecha_publicacion <= NOW())
            ";
            $params = [];

            if ($categoriaId !== null) {
                $sql .= " AND n.categoria_id = :cat ";
                $params[':cat'] = $categoriaId;
            }
            if ($excluirId !== null) {
                $sql .= " AND n.id != :ex ";
                $params[':ex'] = $excluirId;
            }

            $sql .= " ORDER BY n.fecha_publicacion DESC, n.id DESC LIMIT :lim ";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('vcf_noticias_ultimas: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('vcf_noticia_por_slug')) {
    /**
     * Obtiene una noticia completa por su slug. Para la página de detalle.
     */
    function vcf_noticia_por_slug(PDO $pdo, string $slug): ?array
    {
        try {
            $stmt = $pdo->prepare("
                SELECT n.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.color AS categoria_color
                FROM noticias n
                LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
                WHERE n.slug = ?
                  AND n.publicado = 1
                  AND (n.fecha_publicacion IS NULL OR n.fecha_publicacion <= NOW())
                LIMIT 1
            ");
            $stmt->execute([$slug]);
            $noticia = $stmt->fetch(PDO::FETCH_ASSOC);
            return $noticia ?: null;
        } catch (PDOException $e) {
            error_log('vcf_noticia_por_slug: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('vcf_noticias_listar')) {
    /**
     * Lista paginada de noticias (para news.php).
     *
     * @return array ['items' => [...], 'total' => int, 'paginas' => int]
     */
    function vcf_noticias_listar(PDO $pdo, int $pagina = 1, int $porPagina = 9, ?string $categoriaSlug = null): array
    {
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $porPagina;

        try {
            $whereCat = '';
            $params = [];
            if ($categoriaSlug !== null && $categoriaSlug !== '') {
                $whereCat = ' AND c.slug = :slug ';
                $params[':slug'] = $categoriaSlug;
            }

            // Total
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM noticias n
                LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
                WHERE n.publicado = 1
                  AND (n.fecha_publicacion IS NULL OR n.fecha_publicacion <= NOW())
                  $whereCat
            ");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $total = (int) $stmt->fetchColumn();

            // Items
            $stmt = $pdo->prepare("
                SELECT n.id, n.titulo, n.slug, n.resumen, n.imagen_destacada, n.imagen_alt,
                       n.fecha_publicacion, n.autor, n.views,
                       c.nombre AS categoria_nombre, c.slug AS categoria_slug, c.color AS categoria_color
                FROM noticias n
                LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
                WHERE n.publicado = 1
                  AND (n.fecha_publicacion IS NULL OR n.fecha_publicacion <= NOW())
                  $whereCat
                ORDER BY n.fecha_publicacion DESC, n.id DESC
                LIMIT :lim OFFSET :off
            ");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'items'    => $items,
                'total'    => $total,
                'paginas'  => max(1, (int) ceil($total / $porPagina)),
                'pagina'   => $pagina,
            ];
        } catch (PDOException $e) {
            error_log('vcf_noticias_listar: ' . $e->getMessage());
            return ['items' => [], 'total' => 0, 'paginas' => 1, 'pagina' => 1];
        }
    }
}

if (!function_exists('vcf_noticias_categorias_activas')) {
    /**
     * Categorías activas (para filtros y selects del admin).
     */
    function vcf_noticias_categorias_activas(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query("
                SELECT id, nombre, slug, color
                FROM noticias_categorias
                WHERE activa = 1
                ORDER BY orden ASC, nombre ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('vcf_noticias_categorias_activas: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('vcf_noticia_increment_views')) {
    /**
     * Incrementa el contador de views. Llamar en page-detalle.
     */
    function vcf_noticia_increment_views(PDO $pdo, int $id): void
    {
        try {
            $stmt = $pdo->prepare("UPDATE noticias SET views = views + 1 WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Silencioso, no es crítico
        }
    }
}

if (!function_exists('vcf_noticia_url')) {
    /**
     * URL pública de una noticia por slug.
     */
    function vcf_noticia_url(string $slug, string $base = ''): string
    {
        $prefix = $base !== '' ? rtrim($base, '/') . '/' : '';
        return $prefix . 'news.php?slug=' . urlencode($slug);
    }
}

if (!function_exists('vcf_noticia_placeholder_path')) {
    /**
     * Ruta relativa del placeholder según categoría (sin imagen destacada).
     */
    function vcf_noticia_placeholder_path(?string $categoria_slug = null): string
    {
        $editorialSlugs = ['academy-news', 'training', 'player-stories'];
        if ($categoria_slug !== null && in_array($categoria_slug, $editorialSlugs, true)) {
            return 'assets/img/news-placeholder-editorial.jpg';
        }
        return 'assets/img/news-placeholder.jpg';
    }
}

if (!function_exists('vcf_noticia_imagen_url')) {
    /**
     * Resuelve la URL de la imagen destacada, con fallback por categoría.
     */
    function vcf_noticia_imagen_url(?string $imagen, string $base = '', ?string $categoria_slug = null): string
    {
        if (empty($imagen)) {
            $prefix = $base !== '' ? rtrim($base, '/') . '/' : '';
            return $prefix . vcf_noticia_placeholder_path($categoria_slug);
        }
        if (preg_match('#^https?://#i', $imagen)) {
            return $imagen;
        }
        $path = ltrim($imagen, '/');
        return $base !== '' ? rtrim($base, '/') . '/' . $path : $path;
    }
}

if (!function_exists('vcf_fecha_humana')) {
    /**
     * Formatea fecha estilo "May 19, 2026" o relativa ("2 days ago").
     */
    function vcf_fecha_humana(?string $fecha, bool $relativa = false): string
    {
        if (empty($fecha)) return '';

        $ts = strtotime($fecha);
        if ($ts === false) return '';

        if ($relativa) {
            $diff = time() - $ts;
            if ($diff < 60) return 'Just now';
            if ($diff < 3600) return floor($diff / 60) . ' min ago';
            if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
            if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        }
        return date('M j, Y', $ts);
    }
}
