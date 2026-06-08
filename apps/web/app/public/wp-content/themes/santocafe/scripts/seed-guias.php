<?php
/**
 * Seed script — Guías de Café (Santo Café)
 *
 * Creates or updates the initial guide posts and the "guias" category.
 * Run via: php seed-guias.php (from theme root / scripts dir)
 *
 * Safe to re-run: checks by slug before inserting.
 */
define( 'WP_USE_THEMES', false );
require __DIR__ . '/../../../../wp-load.php';

// ============================================================
// Helper
// ============================================================
function sc_seed_guia( array $args ): void {
    $existing = get_page_by_path( $args['slug'], OBJECT, 'post' );

    $data = [
        'post_title'   => $args['title'],
        'post_name'    => $args['slug'],
        'post_content' => $args['content'],
        'post_excerpt' => $args['excerpt'],
        'post_status'  => 'publish',
        'post_author'  => 1,
        'post_type'    => 'post',
    ];

    if ( $existing ) {
        $data['ID'] = $existing->ID;
        $id = wp_update_post( $data, true );
        echo "[UPDATE] {$args['slug']} (#{$id})\n";
    } else {
        $id = wp_insert_post( $data, true );
        echo "[INSERT] {$args['slug']} (#{$id})\n";
    }

    if ( is_wp_error( $id ) ) {
        echo "  ERROR: " . $id->get_error_message() . "\n";
        return;
    }

    // Assign category
    if ( ! empty( $args['category_ids'] ) ) {
        wp_set_post_categories( $id, $args['category_ids'], false );
    }
}

// ============================================================
// Create / get categories
// ============================================================
$cat_guias = get_category_by_slug( 'guias' );
if ( ! $cat_guias ) {
    $result = wp_insert_term( 'Guías', 'category', [ 'slug' => 'guias' ] );
    if ( is_wp_error( $result ) ) {
        echo "[ERROR] No se pudo crear la categoría Guías: " . $result->get_error_message() . "\n";
        exit( 1 );
    }
    $cat_guias_id = $result['term_id'];
    echo "[CATEGORY] Guías creada (#{$cat_guias_id})\n";
} else {
    $cat_guias_id = $cat_guias->term_id;
    echo "[CATEGORY] Guías ya existe (#{$cat_guias_id})\n";
}

$cat_metodos    = get_category_by_slug( 'metodos-de-preparacion' );
if ( ! $cat_metodos ) {
    $cat_metodos_id = wp_insert_term( 'Métodos de preparación', 'category', [
        'slug'   => 'metodos-de-preparacion',
        'parent' => $cat_guias_id,
    ] );
    $cat_metodos_id = $cat_metodos_id['term_id'];
    echo "[CATEGORY] Métodos de preparación creada (#{$cat_metodos_id})\n";
} else {
    $cat_metodos_id = $cat_metodos->term_id;
    echo "[CATEGORY] Métodos de preparación ya existe (#{$cat_metodos_id})\n";
}

$cat_cafe    = get_category_by_slug( 'cafe-de-especialidad' );
if ( ! $cat_cafe ) {
    $cat_cafe_id = wp_insert_term( 'Café de especialidad', 'category', [
        'slug'   => 'cafe-de-especialidad',
        'parent' => $cat_guias_id,
    ] );
    $cat_cafe_id = $cat_cafe_id['term_id'];
    echo "[CATEGORY] Café de especialidad creada (#{$cat_cafe_id})\n";
} else {
    $cat_cafe_id = $cat_cafe->term_id;
    echo "[CATEGORY] Café de especialidad ya existe (#{$cat_cafe_id})\n";
}

// ============================================================
// Guías
// ============================================================

// --- 1. Espresso ---
sc_seed_guia( [
    'title'        => 'Cómo preparar espresso en casa: guía completa',
    'slug'         => 'como-preparar-espresso',
    'excerpt'      => 'Aprendé a preparar un espresso perfecto en casa: molienda, dosis, temperatura y técnica explicados paso a paso.',
    'category_ids' => [ $cat_metodos_id, $cat_guias_id ],
    'content'      => <<<'HTML'
<!-- wp:paragraph -->
<p>El espresso es la base de casi todas las bebidas de café. Cuando está bien preparado, concentra todos los sabores y aromas del grano en apenas 30 ml de líquido intenso y cremoso. Esta guía te explica todo lo que necesitás saber para hacerlo bien en casa.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Qué necesitás para preparar espresso?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Para hacer un buen espresso necesitás tres cosas: una cafetera de espresso (eléctrica o de vapor), un café de especialidad con <strong>molienda espresso</strong> (muy fina, casi como polvo de talco) y agua filtrada entre 88 y 96 °C.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>La calidad del grano es el factor más importante. Un café con puntaje SCA de 84 o más, de proceso lavado, suele dar espressos con buena acidez brillante y cuerpo equilibrado. Los cafés de Brasil o Guatemala con proceso natural aportan más dulzor y cuerpo.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Molienda para espresso</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>La molienda espresso es la más fina de todos los métodos. Debe sentirse como harina muy fina entre los dedos. Si el café sale demasiado rápido (menos de 20 segundos), molé más fino. Si tarda más de 35 segundos, molé más grueso.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Dosis y proporción</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>El estándar de especialidad es una proporción de 1:2 a 1:2,5. Es decir: si usás 18 g de café molido, buscás obtener entre 36 y 45 ml de líquido en taza. El tiempo de extracción ideal es entre 25 y 35 segundos.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Pasos para preparar el espresso perfecto</h2>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true} -->
<ol>
<li>Precalentá la cafetera y la taza durante al menos 5 minutos.</li>
<li>Pesá entre 17 y 19 g de café molido (molienda espresso).</li>
<li>Distribuí el café en el portafiltro de manera uniforme.</li>
<li>Apisoná (tamp) con una presión pareja de aproximadamente 15–20 kg.</li>
<li>Iniciá la extracción y medí el tiempo: deberías obtener entre 30 y 45 ml en 25–35 segundos.</li>
<li>Evaluá: un espresso bien extraído tiene una crema color avellana, sabor balanceado entre dulzor, acidez y amargor.</li>
</ol>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>¿Por qué mi espresso sale amargo?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Si el espresso sale muy amargo, probablemente esté sobre-extraído. Esto puede deberse a molienda demasiado fina, temperatura de agua muy alta, o tiempo de extracción excesivo. Probá moliendo un poco más grueso o reduciendo la temperatura del agua.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Por qué mi espresso sale ácido y aguado?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Un espresso ácido y aguado suele estar sub-extraído. La extracción terminó antes de tiempo. Intentá moler más fino, aumentar la dosis o elevar la temperatura del agua entre 1 y 2 grados.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Cuál es el mejor café para espresso?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Los mejores cafés para espresso son los de cuerpo alto y notas dulces: Brasil (natural, chocolate, nueces), Guatemala (proceso lavado, caramelo, frutos secos) y Costa Rica (balanceado, muy limpio). En Santo Café tenemos <a href="/producto/santo-ouro-doce/">Santo Ouro Doce (Brasil)</a>, <a href="/producto/santo-sereno/">Santo Sereno (Guatemala)</a> y <a href="/producto/los-santos/">Los Santos (Costa Rica)</a> ideales para esta preparación.</p>
<!-- /wp:paragraph -->
HTML,
] );

// --- 2. Italiana (moka) ---
sc_seed_guia( [
    'title'        => 'Cómo preparar café en cafetera italiana (moka): paso a paso',
    'slug'         => 'como-preparar-cafe-en-italiana',
    'excerpt'      => 'Dominá la cafetera italiana o moka con esta guía: qué café usar, cómo molarla, cuánta agua poner y los errores más comunes.',
    'category_ids' => [ $cat_metodos_id, $cat_guias_id ],
    'content'      => <<<'HTML'
<!-- wp:paragraph -->
<p>La cafetera italiana (o moka) es uno de los métodos más populares en los hogares latinoamericanos. Con la técnica correcta y un buen café de especialidad, podés obtener un café concentrado y sabroso sin necesidad de equipos caros.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Qué tipo de molienda usar en la cafetera italiana?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>La molienda para italiana es media-fina: más gruesa que el espresso, pero mucho más fina que el filtro. Si molés demasiado fino, el café no pasa o sale amargo. Si molés muy grueso, el café sale aguado y sin cuerpo.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Una buena referencia: la molienda debe sentirse como azúcar molida fina. Si apretás un poco entre los dedos, forma una pequeña pelota que se desarma enseguida.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Cuánta agua poner en la cafetera italiana</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Llenás la caldera inferior hasta la válvula de seguridad (la tuerca metálica lateral). Nunca por encima de esa marca. Usá agua filtrada y, si podés, precalentada para reducir el tiempo de extracción y preservar mejor los aromas.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Pasos para preparar café en italiana</h2>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true} -->
<ol>
<li>Llenás la caldera inferior hasta la válvula de seguridad con agua filtrada.</li>
<li>Colocás el filtro (embudo) y lo llenás con café molido (molienda italiana) sin apisonar — el café debe quedar suelto y al ras.</li>
<li>Enroscás bien la parte superior.</li>
<li>Ponés la cafetera a fuego medio-bajo.</li>
<li>Cuando empieza a salir el café, bajás el fuego al mínimo.</li>
<li>Retirás del fuego cuando el borboteo cambia de tono (se vuelve más seco y esporádico).</li>
<li>Removés el café en la parte superior antes de servir para homogeneizar.</li>
</ol>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>¿Por qué el café de italiana sale amargo?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>El café de italiana tiende a sobrepasar la temperatura óptima de extracción si se deja a fuego alto. Las principales causas de amargor son: fuego demasiado alto, molienda muy fina, o dejarla en el fuego después de que ya terminó de subir el café. Bajá el fuego apenas empieza a salir el café.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Qué café es mejor para la italiana?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Para la italiana funcionan bien los cafés de cuerpo medio a alto, con notas chocolatosas o a frutos secos. Nuestro <a href="/producto/macondo/">Macondo (Colombia)</a> y <a href="/producto/santo-yungas/">Santo Yungas (Bolivia)</a> son excelentes opciones: equilibrados, con buen cuerpo y sin acidez excesiva que se amplifique con este método.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Cuándo hay que limpiar la cafetera italiana?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Después de cada uso, enjuagá todas las partes con agua caliente sin jabón. El jabón puede dejar residuos que alteran el sabor. La junta de goma y el filtro deben revisarse cada 6–12 meses y reemplazarse si tienen grietas.</p>
<!-- /wp:paragraph -->
HTML,
] );

// --- 3. Filtro / V60 ---
sc_seed_guia( [
    'title'        => 'Cómo preparar café de filtro (V60 y otros): guía para principiantes',
    'slug'         => 'como-preparar-cafe-de-filtro',
    'excerpt'      => 'Todo lo que necesitás saber para preparar café de filtro en casa: V60, Chemex y métodos similares explicados paso a paso.',
    'category_ids' => [ $cat_metodos_id, $cat_guias_id ],
    'content'      => <<<'HTML'
<!-- wp:paragraph -->
<p>El café de filtro (o pour-over) es el método favorito de los amantes del café de especialidad porque permite apreciar con mayor claridad los sabores originales del grano. Con un V60, Chemex, Kalita o Hario, podés preparar un café limpio, aromático y complejo.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Qué necesitás para preparar café de filtro?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Necesitás: un dripper (V60, Chemex o similar), filtros de papel compatibles, una kettle o pava (idealmente con cuello de ganso), una balanza, agua filtrada a 92–96 °C, y café de especialidad con molienda filtro (media-gruesa).</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Molienda para café de filtro</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>La molienda para filtro es media-gruesa: similar a la sal gruesa o al azúcar rubio. Es claramente más gruesa que la de espresso o italiana. Una molienda correcta permite que el agua fluya a través del café en 3 a 4 minutos sin saturar el filtro.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Proporción y dosis</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>La proporción estándar para pour-over es 1:15 a 1:17. Es decir, 15 a 17 g de agua por cada 1 g de café. Para una taza grande de 300 ml, usá entre 18 y 20 g de café molido. Siempre pesá con balanza para resultados consistentes.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Pasos para preparar café de filtro</h2>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true} -->
<ol>
<li>Enjuagá el filtro de papel con agua caliente (esto elimina el sabor a papel y precalienta el dripper).</li>
<li>Colocá el café molido en el filtro. Hacé un pequeño hueco en el centro.</li>
<li>Pre-infusión (bloom): vertí el doble del peso del café en agua (ej. 36 ml para 18 g) y esperá 30–45 segundos. Esto libera el CO₂ y prepara el café para una extracción pareja.</li>
<li>Continuá vertiendo el agua en círculos lentos y uniformes, de adentro hacia afuera, en 3 o 4 adiciones.</li>
<li>El tiempo total (desde el bloom) debe ser entre 3 y 4 minutos.</li>
<li>Servís y disfrutás en el momento.</li>
</ol>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>¿Qué café es mejor para filtro?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Los cafés lavados con alta acidez y notas florales o frutales brillan en filtro porque el método resalta esa clareza. Nuestros <a href="/producto/cielo-andino/">Cielo Andino (Colombia)</a> y <a href="/producto/camino-inca/">Camino Inca (Perú)</a> son ideales: acidez fresca, notas limpias y un retrogusto largo. El <a href="/producto/santo-equilibrio/">Santo Equilibrio (Colombia)</a> también es una excelente opción por su equilibrio.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Por qué mi filtro sale muy ácido?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Una acidez excesiva puede indicar sub-extracción. Probá: moler un poco más fino, elevar la temperatura del agua a 94–96 °C, o aumentar el tiempo de bloom. También verificá que estés usando la proporción correcta.</p>
<!-- /wp:paragraph -->
HTML,
] );

// --- 4. ¿Qué es el café de especialidad? ---
sc_seed_guia( [
    'title'        => 'Qué es el café de especialidad y por qué importa el puntaje SCA',
    'slug'         => 'que-es-el-cafe-de-especialidad',
    'excerpt'      => 'Descubrí qué diferencia al café de especialidad del café comercial, qué significa el puntaje SCA y por qué es importante para tu taza.',
    'category_ids' => [ $cat_cafe_id, $cat_guias_id ],
    'content'      => <<<'HTML'
<!-- wp:paragraph -->
<p>Cuando hablamos de café de especialidad, hablamos del segmento de mayor calidad dentro de la cadena del café. Pero ¿qué lo diferencia del café que encontrás en el supermercado? ¿Qué significa que un café tenga 84 o 85 puntos SCA? En esta guía te explicamos todo.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Qué es el café de especialidad?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>El café de especialidad es aquel que obtiene un puntaje de <strong>80 puntos o más</strong> en la escala de la Specialty Coffee Association (SCA). Para llegar a ese puntaje, el café debe cumplir con estrictos estándares en toda su cadena: desde el origen y las condiciones de cultivo, hasta la cosecha, el procesamiento, el tostado y la preparación.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>A diferencia del café comercial (que mezcla granos de distintas procedencias y calidades), el café de especialidad suele ser <strong>single origin</strong> (de un solo origen, finca o región) y permite identificar exactamente de dónde viene cada lote.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Qué significa el puntaje SCA?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>La SCA (Specialty Coffee Association) desarrolló un sistema de evaluación sensorial estandarizado que evalúa el café en una escala de 0 a 100. Los catadores certificados (Q Graders) analizan:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li><strong>Fragancia y aroma</strong>: complejidad de los aromas en seco y en taza.</li>
<li><strong>Sabor (flavor)</strong>: rango de sabores durante la cata.</li>
<li><strong>Retrogusto (aftertaste)</strong>: calidad y duración del sabor que queda.</li>
<li><strong>Acidez</strong>: calidad y intensidad de la acidez.</li>
<li><strong>Cuerpo</strong>: textura del café en boca.</li>
<li><strong>Balance</strong>: armonía entre todos los atributos.</li>
<li><strong>Dulzor, uniformidad y limpieza de taza</strong>.</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>Un café de 80–84 puntos es "muy bueno". De 85–89, "excelente". De 90 o más, "sobresaliente" — una rareza absoluta.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Cómo afecta el proceso al sabor?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>El proceso de beneficio (cómo se retira la pulpa del café después de la cosecha) influye enormemente en el perfil de sabor:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li><strong>Lavado</strong>: el más común en los Andes. Produce tazas limpias, con acidez brillante y notas florales o frutales bien definidas.</li>
<li><strong>Natural (seco)</strong>: el grano se seca con la pulpa. Resulta en cafés con cuerpo alto, frutas tropicales y mucho dulzor. Nuestro <a href="/producto/santo-ouro-doce/">Santo Ouro Doce (Brasil)</a> es natural.</li>
<li><strong>Honey / fermentado</strong>: procesos intermedios o experimentales que buscan combinar lo mejor de los dos mundos. Nuestro <a href="/producto/cielo-andino/">Cielo Andino (Colombia)</a> usa proceso lavado y fermentado.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>¿Cuánto cuesta el café de especialidad?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>El café de especialidad tiene un precio mayor que el comercial porque involucra un costo de producción más alto: fincas más pequeñas, cosecha selectiva a mano, procesos más cuidadosos y una cadena de trazabilidad transparente. En Chile, el precio típico de un café de especialidad de 250 g ronda los $5.000 a $10.000 CLP.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Vale la pena el café de especialidad?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Para quien aprecia el sabor en su taza diaria, definitivamente sí. El café de especialidad tiene mayor complejidad, más matices y generalmente menor cantidad de defectos que el comercial. Una vez que conocés la diferencia, es difícil volver atrás.</p>
<!-- /wp:paragraph -->
HTML,
] );

// --- 5. Café lavado vs natural ---
sc_seed_guia( [
    'title'        => 'Café lavado vs natural: diferencias de sabor y proceso',
    'slug'         => 'cafe-lavado-vs-natural',
    'excerpt'      => 'Aprende la diferencia entre el proceso lavado y natural en el café de especialidad, y cómo elegir el que mejor se adapta a tu gusto.',
    'category_ids' => [ $cat_cafe_id, $cat_guias_id ],
    'content'      => <<<'HTML'
<!-- wp:paragraph -->
<p>Cuando ves en el empaque de tu café "proceso lavado" o "proceso natural", hace referencia a cómo se retiró la pulpa del fruto del café después de la cosecha. Esta etapa tiene un impacto enorme en el sabor final de tu taza.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Proceso lavado: taza limpia y ácida</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>En el proceso lavado, se retira toda la pulpa y el mucílago del grano con agua antes de secarlo. Esto produce cafés con una taza más limpia, donde los sabores propios del terroir (altitud, suelo, variedad) se expresan con más claridad. La acidez suele ser más brillante y definida, y el cuerpo tiende a ser más liviano a medio.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>La mayoría de los cafés de los Andes (Colombia, Perú, Bolivia, Guatemala) son lavados. En Santo Café, nuestros <a href="/producto/macondo/">Macondo</a>, <a href="/producto/camino-inca/">Camino Inca</a>, <a href="/producto/santo-yungas/">Santo Yungas</a> y <a href="/producto/santo-sereno/">Santo Sereno</a> son procesos lavados.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Proceso natural: cuerpo alto y notas frutales</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>En el proceso natural (o seco), el fruto entero se seca al sol sin retirar la pulpa. El grano permanece dentro del fruto durante semanas, lo que permite que los azúcares de la pulpa migren hacia el grano. El resultado es un café con mucho más cuerpo, dulzor pronunciado y notas a frutas tropicales, bayas o chocolate.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nuestro <a href="/producto/santo-ouro-doce/">Santo Ouro Doce de Brasil</a> es natural: vas a notar notas frutales intensas y un cuerpo cremoso que lo hace ideal para espresso y moka.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>¿Cuál es mejor para cada método de preparación?</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li><strong>Filtro / V60</strong>: los lavados brillan acá, porque el método resalta la limpieza y la acidez.</li>
<li><strong>Espresso</strong>: los naturales son ideales porque su dulzor y cuerpo alto funcionan muy bien bajo presión.</li>
<li><strong>Italiana (moka)</strong>: ambos funcionan; los naturales dan un resultado más intenso y dulce, los lavados más equilibrado.</li>
<li><strong>Prensa francesa</strong>: los naturales con cuerpo alto son excelentes en este método de inmersión.</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>¿Qué es el proceso honey o fermentado?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>El proceso honey es un punto intermedio: se retira la pulpa pero se deja todo o parte del mucílago sobre el grano durante el secado. Hay distintos niveles (yellow honey, red honey, black honey) según cuánto mucílago queda. El resultado combina la limpieza del lavado con el dulzor del natural.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>El proceso fermentado es una categoría más amplia que incluye técnicas experimentales como fermentaciones anaeróbicas, con levaduras específicas o con maceración carbónica. Nuestro <a href="/producto/cielo-andino/">Cielo Andino (Colombia)</a> usa proceso lavado y fermentado, lo que le da una complejidad extra.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>¿Cómo sé qué proceso tiene mi café?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>En Santo Café siempre indicamos el proceso de beneficio en el empaque y en cada página de producto. Si un café no indica su proceso, probablemente sea café comercial que mezcla distintos lotes sin trazabilidad.</p>
<!-- /wp:paragraph -->
HTML,
] );

echo "\n✅ Seeding de guías completado.\n";
