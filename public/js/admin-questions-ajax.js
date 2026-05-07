/**
 * Gestion AJAX de la recherche et du tri des questions dans le dashboard admin
 */
(function() {
    'use strict';

    // Éléments du DOM
    const searchInput = document.getElementById('searchQuestion');
    const categorySelect = document.getElementById('categorie');
    const questionsTableBody = document.querySelector('#questionsTable tbody');
    const clearSearchBtn = document.getElementById('clearSearch');
    const resultsCount = document.getElementById('resultsCount');
    const loadingSpinner = document.getElementById('loadingSpinner');

    // Headers du tableau pour le tri
    const tableHeaders = document.querySelectorAll('#questionsTable thead th[data-sort]');

    // État actuel du tri
    let currentSort = {
        field: 'ordre',
        order: 'ASC'
    };

    /**
     * Effectue une requête AJAX pour charger les questions
     */
    function loadQuestions() {
        const searchValue = searchInput ? searchInput.value.trim() : '';
        const categoryValue = categorySelect ? categorySelect.value : 'all';

        // Afficher le spinner de chargement
        if (loadingSpinner) {
            loadingSpinner.classList.remove('d-none');
        }

        // Construction de l'URL avec paramètres
        const params = new URLSearchParams({
            search: searchValue,
            categorie: categoryValue,
            sortBy: currentSort.field,
            sortOrder: currentSort.order
        });

        const url = `/admin/questions/search?${params.toString()}`;

        // Requête AJAX avec fetch
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Vérifier si on est redirigé vers la page de connexion
            if (response.redirected && response.url.includes('/login')) {
                window.location.href = '/login';
                throw new Error('Session expirée, redirection vers login');
            }
            
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.text();
        })
        .then(html => {
            // Vérifier si le HTML retourné est bien un tbody (pas une page complète)
            if (html.includes('<!DOCTYPE html>') || html.includes('<html')) {
                // On a reçu une page complète au lieu du fragment attendu
                console.error('Reçu une page complète au lieu du fragment. Session probablement expirée.');
                window.location.reload();
                return;
            }

            // Mettre à jour le contenu du tableau (innerHTML au lieu de outerHTML)
            if (questionsTableBody) {
                questionsTableBody.innerHTML = html;
            }

            // Masquer le spinner
            if (loadingSpinner) {
                loadingSpinner.classList.add('d-none');
            }

            // Mettre à jour le compteur de résultats
            updateResultsCount();

            // Mettre à jour les indicateurs de tri dans les en-têtes
            updateSortIndicators();
        })
        .catch(error => {
            console.error('Erreur lors du chargement des questions:', error);
            
            if (loadingSpinner) {
                loadingSpinner.classList.add('d-none');
            }

            // Afficher un message d'erreur
            if (questionsTableBody) {
                questionsTableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-danger py-4">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">Une erreur est survenue lors du chargement des questions.</p>
                            <p class="text-muted small">Vérifiez que vous êtes bien connecté en tant qu'administrateur.</p>
                        </td>
                    </tr>
                `;
            }
        });
    }

    /**
     * Mettre à jour le compteur de résultats
     */
    function updateResultsCount() {
        if (!resultsCount) return;

        const tbody = document.querySelector('#questionsTable tbody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        const emptyMessage = tbody.querySelector('td[colspan]');
        
        if (emptyMessage) {
            resultsCount.textContent = '0 question(s) affichée(s)';
        } else {
            resultsCount.textContent = `${rows.length} question(s) affichée(s)`;
        }
    }

    /**
     * Mettre à jour les indicateurs de tri dans les en-têtes
     */
    function updateSortIndicators() {
        tableHeaders.forEach(header => {
            const sortField = header.getAttribute('data-sort');
            const icon = header.querySelector('.sort-icon');
            
            if (icon) {
                icon.remove();
            }

            if (sortField === currentSort.field) {
                const newIcon = document.createElement('i');
                newIcon.className = 'bi ' + (currentSort.order === 'ASC' ? 'bi-arrow-up' : 'bi-arrow-down') + ' ms-1 sort-icon';
                header.appendChild(newIcon);
            }
        });
    }

    /**
     * Gérer le clic sur les en-têtes de colonne pour le tri
     */
    function handleSort(event) {
        const header = event.currentTarget;
        const sortField = header.getAttribute('data-sort');

        if (!sortField) return;

        // Si on clique sur la même colonne, inverser l'ordre
        if (currentSort.field === sortField) {
            currentSort.order = currentSort.order === 'ASC' ? 'DESC' : 'ASC';
        } else {
            // Sinon, trier par cette nouvelle colonne en ASC
            currentSort.field = sortField;
            currentSort.order = 'ASC';
        }

        loadQuestions();
    }

    /**
     * Initialisation des événements
     */
    function init() {
        // Empêcher la soumission des formulaires (on utilise AJAX)
        const searchForm = document.getElementById('searchForm');
        const categoryForm = document.getElementById('categoryForm');

        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                loadQuestions();
            });
        }

        if (categoryForm) {
            categoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
            });
        }

        // Recherche au fil de la saisie (avec debounce)
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadQuestions();
                }, 300); // Attendre 300ms après la dernière frappe
            });
        }

        // Changement de catégorie
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                loadQuestions();
            });
        }

        // Bouton effacer la recherche
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    loadQuestions();
                }
            });
        }

        // Tri sur les colonnes
        tableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', handleSort);
        });

        // Initialiser les indicateurs de tri
        updateSortIndicators();
    }

    // Lancer l'initialisation quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
