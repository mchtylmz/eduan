@php
$tableMessages = [
    "view" => __("Görüntüle"), "translate" => __("Çeviriler"), "edit" => __("Düzenle"), "delete" => $deleteLabel ?? __("Sil"),
    "confirm" => $confirm, "ok" => __("Tamam"), "cancel" => __("Vazgeç"),
    "error" => __("tables.load_error"),
    "cardView" => __("tables.card_view"), "tableView" => __("tables.table_view"),
    "empty" => __("tables.no_records"), "loading" => __("tables.loading"),
    "lesson" => __("Ders"), "topic" => __("Konu"), "code" => __("Kodu"), "language" => __("Dil"),
    "seconds" => __("sn"), "time" => __("Süre"), "sort" => __("Sıra"),
    "votes" => __("Değerlendirme"), "average" => __("Ortalama"), "views" => __("Görüntüleme"), "report" => __("Rapor"), "created" => __("Oluşturma"),
    "sections" => __("Sınav Bölümleri"), "results" => __("Sonuçlar"), "favorites" => __("Favoriye Ekleyenler"), "questions" => __("Sorular"),
    "status" => __("Durum"), "emailVerified" => __("E-posta Onay"), "lastLogin" => __("Son Giriş"),
];
@endphp
data-table-messages='@json($tableMessages)'
