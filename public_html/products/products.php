<?php
// Define categories
$categories = [
    "category1" => "Desain PPT",
    "category2" => "Asistensi Tugas Akhir",
    "category3" => "Analisis Data (Mahasiswa)",
    "category4" => "Analisis Data (Profesional)",
    "category5" => "E - Learning Research",
    "category6" => "Translasi",
    "category7" => "Web Design",
];

// Template product details with placeholders
$productTemplate = [
    "price" => 999999999999,
    "category" => '',
    "category_key" => '',
    "image" => "https://placehold.co/400x400",  // Default placeholder image
    "description" => "Default description",
    "about_this_item" => "Default description for about_this_item.",
    "list" => [
        "Lorem ipsum dolor sit amet.",
        "Consectetur adipiscing elit.",
        "Vivamus luctus urna sed urna.",
    ],
    "link" => ""
];

// Function to generate product data with specific content
function createProduct($id, $name, $price, $categoryKey, $image, $description, $aboutThisItem, $list, $categories, $productTemplate)
{
    if (empty($price)) {
        throw new InvalidArgumentException("Price cannot be empty.");
    }

    return array_merge($productTemplate, [
        "id" => $id,
        "name" => $name,
        "price" => $price,
        "category" => $categories[$categoryKey],
        "category_key" => $categoryKey,
        "image" => !empty($image) ? $image : $productTemplate['image'],
        "description" => $description,
        "about_this_item" => !empty($aboutThisItem) ? $aboutThisItem : $productTemplate['about_this_item'],
        "list" => $list,
        "link" => "./details/index.php?id=$id"
    ]);
}

// Define products with different content
$products = [
    createProduct(
        1,
        "PPT 1",
        100,
        "category1",
        "https://placehold.co/400x400/ff6347/ffffff",
        "PPT 1 untuk presentasi bisnis dengan desain modern.",
        "Deskripsi lengkap mengenai PPT 1, cocok untuk keperluan bisnis.",
        ["Desain profesional", "Customizable template", "Mudah digunakan"],
        $categories,
        $productTemplate
    ),

    createProduct(
        2,
        "Skripsi",
        200,
        "category2",
        "https://placehold.co/400x400/4682b4/ffffff",
        "Skripsi dengan analisis mendalam dan format akademis.",
        "Skripsi lengkap dengan bab-bab yang terstruktur.",
        ["Metode penelitian", "Analisis data", "Penulisan akademis"],
        $categories,
        $productTemplate
    ),

    createProduct(
        3,
        "PPT 2",
        150,
        "category1",
        "https://placehold.co/400x400/32cd32/ffffff",
        "PPT 2 untuk presentasi teknologi dengan tampilan futuristik.",
        "Deskripsi lengkap mengenai PPT 2, ideal untuk presentasi teknologi.",
        ["Desain futuristik", "Kesesuaian dengan teknologi terbaru", "Visual yang menarik"],
        $categories,
        $productTemplate
    ),

    createProduct(
        4,
        "Frontpage",
        300,
        "category7",
        "https://placehold.co/400x400/daa520/ffffff",
        "Desain frontpage website yang menarik dan responsif.",
        "Frontpage dengan desain yang cocok untuk website bisnis.",
        ["Desain responsif", "Optimasi SEO", "Pengalaman pengguna yang baik"],
        $categories,
        $productTemplate
    ),

    createProduct(
        5,
        "Tesis",
        120,
        "category2",
        "https://placehold.co/400x400/ff4500/ffffff",
        "Tesis dengan pembahasan mendalam tentang analisis data.",
        "Tesis dengan analisis data yang sangat detail dan lengkap.",
        ["Analisis statistik", "Pengolahan data", "Penulisan akademik"],
        $categories,
        $productTemplate
    ),

    createProduct(
        6,
        "E-Learning Research",
        250,
        "category5",
        "",
        "E-Learning Research untuk mendukung penelitian akademis.",
        "",
        ["Data terkini", "Pendekatan interaktif", "Mudah diakses"],
        $categories,
        $productTemplate
    )
];
