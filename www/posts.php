<?php
session_start();
require_once __DIR__ . '/user_store.php';

require_authentication();
$loggedInUsername = current_username();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Posts Dashboard</h1>
                <p class="mb-0 text-muted small">Logged in as <strong><?php echo h((string) $loggedInUsername); ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="listUsers.php">Users</a>
                <a class="btn btn-outline-primary" href="products.php">Products</a>
                <a class="btn btn-outline-danger" href="logout.php">Logout</a>
            </div>
        </div>

        <div id="alertBox" class="alert d-none" role="alert"></div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 bg-white">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">API Login (JWT)</h2>
                        <form id="loginForm" class="mb-3">
                            <div class="mb-3">
                                <label for="apiUsername" class="form-label">Username</label>
                                <input id="apiUsername" type="text" class="form-control" value="<?php echo h((string) $loggedInUsername); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="apiPassword" class="form-label">Password</label>
                                <input id="apiPassword" type="password" class="form-control" placeholder="Enter account password" required>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-success" type="submit">Get Token</button>
                                <button id="clearTokenBtn" class="btn btn-outline-secondary" type="button">Clear</button>
                            </div>
                        </form>
                        <div>
                            <span id="tokenState" class="badge text-bg-light border">No token loaded</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card h-100 bg-white">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Create / Edit Post</h2>
                            <button id="refreshBtn" class="btn btn-outline-primary btn-sm" type="button">Refresh Posts</button>
                        </div>
                        <form id="postForm">
                            <input type="hidden" id="editingPostId" value="">
                            <div class="mb-3">
                                <label for="postTitle" class="form-label">Title</label>
                                <input id="postTitle" type="text" class="form-control" minlength="5" required>
                            </div>
                            <div class="mb-3">
                                <label for="postContent" class="form-label">Content</label>
                                <textarea id="postContent" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button id="submitPostBtn" class="btn btn-primary" type="submit">Create Post</button>
                                <button id="cancelEditBtn" class="btn btn-outline-secondary d-none" type="button">Cancel Edit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4 bg-white">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Posts</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Content</th>
                                <th>User</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="postsTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Loading posts...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const alertBox = document.getElementById('alertBox');
        const tokenState = document.getElementById('tokenState');
        const postsTableBody = document.getElementById('postsTableBody');
        const editingPostIdInput = document.getElementById('editingPostId');
        const postTitleInput = document.getElementById('postTitle');
        const postContentInput = document.getElementById('postContent');
        const submitPostBtn = document.getElementById('submitPostBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');

        function showAlert(message, type) {
            alertBox.className = `alert alert-${type}`;
            alertBox.textContent = message;
        }

        function hideAlert() {
            alertBox.className = 'alert d-none';
            alertBox.textContent = '';
        }

        function getToken() {
            return localStorage.getItem('posts_api_token') || '';
        }

        function setToken(token) {
            localStorage.setItem('posts_api_token', token);
            refreshTokenState();
        }

        function clearToken() {
            localStorage.removeItem('posts_api_token');
            refreshTokenState();
        }

        function refreshTokenState() {
            const token = getToken();
            if (!token) {
                tokenState.textContent = 'No token loaded';
                return;
            }
            tokenState.textContent = `Token loaded (${token.slice(0, 14)}...)`;
        }

        async function apiRequest(url, options = {}) {
            const response = await fetch(url, options);
            let json = null;
            try {
                json = await response.json();
            } catch (_error) {
                json = null;
            }

            if (!response.ok) {
                const message = json && json.message ? json.message : `Request failed (${response.status})`;
                const error = new Error(message);
                error.details = json;
                error.status = response.status;
                throw error;
            }

            return json;
        }

        function resetForm() {
            editingPostIdInput.value = '';
            postTitleInput.value = '';
            postContentInput.value = '';
            submitPostBtn.textContent = 'Create Post';
            cancelEditBtn.classList.add('d-none');
        }

        function beginEdit(post) {
            editingPostIdInput.value = String(post.id);
            postTitleInput.value = post.title || '';
            postContentInput.value = post.content || '';
            submitPostBtn.textContent = 'Update Post';
            cancelEditBtn.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function loadPosts() {
            const json = await apiRequest('/api/posts');
            const posts = Array.isArray(json.data) ? json.data : [];

            if (posts.length === 0) {
                postsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No posts yet.</td></tr>';
                return;
            }

            postsTableBody.innerHTML = posts.map((post) => {
                const safeTitle = String(post.title || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const safeContent = String(post.content || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const safeUser = String(post.user_id || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const safeCreated = String(post.created_at || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                return `
                    <tr>
                        <td>${post.id}</td>
                        <td>${safeTitle}</td>
                        <td>${safeContent}</td>
                        <td>${safeUser}</td>
                        <td>${safeCreated}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-warning" data-action="edit" data-id="${post.id}">Edit</button>
                                <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${post.id}">Delete</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        document.getElementById('loginForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            hideAlert();

            const username = document.getElementById('apiUsername').value.trim();
            const password = document.getElementById('apiPassword').value;

            try {
                const json = await apiRequest('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password }),
                });
                setToken(json.data.token);
                showAlert('API token loaded successfully.', 'success');
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        });

        document.getElementById('clearTokenBtn').addEventListener('click', () => {
            clearToken();
            showAlert('Token cleared.', 'secondary');
        });

        document.getElementById('refreshBtn').addEventListener('click', async () => {
            hideAlert();
            try {
                await loadPosts();
                showAlert('Posts refreshed.', 'info');
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        });

        document.getElementById('postForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            hideAlert();

            const token = getToken();
            if (!token) {
                showAlert('Please get a JWT token first.', 'warning');
                return;
            }

            const editingId = editingPostIdInput.value;
            const payload = {
                title: postTitleInput.value.trim(),
                content: postContentInput.value.trim(),
            };

            try {
                if (editingId === '') {
                    await apiRequest('/api/posts', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                        },
                        body: JSON.stringify(payload),
                    });
                    showAlert('Post created successfully.', 'success');
                } else {
                    await apiRequest(`/api/posts/${editingId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                        },
                        body: JSON.stringify(payload),
                    });
                    showAlert('Post updated successfully.', 'success');
                }

                resetForm();
                await loadPosts();
            } catch (error) {
                if (error.details && error.details.errors) {
                    const entries = Object.entries(error.details.errors);
                    const firstMessage = entries.length > 0 && Array.isArray(entries[0][1]) ? entries[0][1][0] : error.message;
                    showAlert(firstMessage || error.message, 'danger');
                } else {
                    showAlert(error.message, 'danger');
                }
            }
        });

        cancelEditBtn.addEventListener('click', () => {
            resetForm();
            hideAlert();
        });

        postsTableBody.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            const action = target.dataset.action;
            const postId = target.dataset.id;
            if (!action || !postId) {
                return;
            }

            if (action === 'edit') {
                const row = target.closest('tr');
                if (!row) {
                    return;
                }

                const post = {
                    id: postId,
                    title: row.children[1].textContent || '',
                    content: row.children[2].textContent || '',
                };
                beginEdit(post);
                hideAlert();
                return;
            }

            if (action === 'delete') {
                const token = getToken();
                if (!token) {
                    showAlert('Please get a JWT token first.', 'warning');
                    return;
                }

                if (!confirm('Delete this post?')) {
                    return;
                }

                try {
                    await apiRequest(`/api/posts/${postId}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                        },
                    });
                    showAlert('Post deleted successfully.', 'success');
                    await loadPosts();
                } catch (error) {
                    showAlert(error.message, 'danger');
                }
            }
        });

        refreshTokenState();
        loadPosts().catch((error) => {
            showAlert(error.message, 'danger');
            postsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load posts.</td></tr>';
        });
    </script>
</body>
</html>