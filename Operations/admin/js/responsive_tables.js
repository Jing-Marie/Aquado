// =====================================================
// RESPONSIVE TABLES
// On phones each table row is shown as a card (see style.css),
// so every cell gets its column heading as a label.
// =====================================================

document.addEventListener("DOMContentLoaded", function() {

    document.querySelectorAll(".data-table").forEach(function(table) {

        const labels = Array.from(table.querySelectorAll("thead th")).map(function(th) {
            return th.textContent.trim();
        });

        table.querySelectorAll("tbody tr").forEach(function(row) {

            row.querySelectorAll(":scope > td").forEach(function(cell, i) {

                if (!cell.hasAttribute("colspan") && labels[i]) {
                    cell.setAttribute("data-label", labels[i]);
                }

            });

        });

    });

});
