<script setup>
import { ref, onMounted } from "vue";
import api from "./lib/axios";

const email = ref("admin@example.com");
const password = ref("");
const token = ref(localStorage.getItem("token") || "");
const items = ref([]);
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const editingId = ref(null);

const form = ref({
  name: "",
  category: "",
  stock: 0,
  price: 0,
  description: "",
});

const isLoggedIn = ref(Boolean(token.value));

async function login() {
  loading.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    const response = await api.post("/login", {
      email: email.value,
      password: password.value,
    });

    token.value = response.data.token;
    localStorage.setItem("token", token.value);
    isLoggedIn.value = true;
    password.value = "";
    successMessage.value = "Login berhasil.";
    await fetchItems();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || "Login gagal. Periksa akun dan koneksi API.";
  } finally {
    loading.value = false;
  }
}

async function fetchItems() {
  errorMessage.value = "";

  try {
    const response = await api.get("/items");
    items.value = response.data;
  } catch (error) {
    errorMessage.value = error.response?.data?.message || "Gagal mengambil data barang.";

    if (error.response?.status === 401) {
      logoutLocal();
    }
  }
}

function resetForm() {
  editingId.value = null;
  form.value = {
    name: "",
    category: "",
    stock: 0,
    price: 0,
    description: "",
  };
}

function editItem(item) {
  editingId.value = item.id;
  form.value = {
    name: item.name,
    category: item.category,
    stock: item.stock,
    price: item.price,
    description: item.description || "",
  };

  successMessage.value = "";
  errorMessage.value = "";
}

async function saveItem() {
  saving.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    const payload = {
      name: form.value.name,
      category: form.value.category,
      stock: Number(form.value.stock),
      price: Number(form.value.price),
      description: form.value.description || null,
    };

    if (editingId.value !== null) {
      await api.put(`/items/${editingId.value}`, payload);
      successMessage.value = "Barang berhasil diperbarui.";
    } else {
      await api.post("/items", payload);
      successMessage.value = "Barang berhasil ditambahkan.";
    }

    resetForm();
    await fetchItems();
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message ||
      Object.values(error.response?.data?.errors || {})
        .flat()
        .join(" ") ||
      "Gagal menyimpan barang.";
  } finally {
    saving.value = false;
  }
}

async function deleteItem(item) {
  const confirmed = window.confirm(`Yakin ingin menghapus barang "${item.name}"?`);

  if (!confirmed) return;

  errorMessage.value = "";
  successMessage.value = "";

  try {
    await api.delete(`/items/${item.id}`);
    successMessage.value = "Barang berhasil dihapus.";
    await fetchItems();
  } catch (error) {
    errorMessage.value = error.response?.data?.message || "Gagal menghapus barang.";
  }
}

function logoutLocal() {
  localStorage.removeItem("token");
  token.value = "";
  isLoggedIn.value = false;
  items.value = [];
  resetForm();
  password.value = "";
}

async function logout() {
  try {
    await api.post("/logout");
  } catch {
    // Tetap hapus sesi lokal jika token sudah tidak berlaku.
  } finally {
    logoutLocal();
    successMessage.value = "";
    errorMessage.value = "";
  }
}

onMounted(() => {
  if (isLoggedIn.value) {
    fetchItems();
  }
});
</script>

<template>
  <main class="page">
    <section v-if="!isLoggedIn" class="login-card">
      <h1>Inventory Manager</h1>
      <p class="muted">Masuk untuk mengelola data barang.</p>

      <form @submit.prevent="login">
        <label for="email">Email</label>
        <input id="email" v-model="email" type="email" required />

        <label for="password">Password</label>
        <input id="password" v-model="password" type="password" required />

        <button type="submit" :disabled="loading">
          {{ loading ? "Memproses..." : "Login" }}
        </button>
      </form>
    </section>

    <section v-else class="dashboard">
      <header class="header">
        <div>
          <h1>Inventory Manager</h1>
          <p class="muted">Kelola persediaan barang.</p>
        </div>
        <button class="secondary" @click="logout">Logout</button>
      </header>

      <section class="panel">
        <h2>{{ editingId !== null ? "Edit Barang" : "Tambah Barang" }}</h2>

        <form class="item-form" @submit.prevent="saveItem">
          <label for="name">Nama barang</label>
          <input id="name" v-model="form.name" required />

          <label for="category">Kategori</label>
          <input id="category" v-model="form.category" required />

          <div class="form-grid">
            <div>
              <label for="stock">Stok</label>
              <input id="stock" v-model.number="form.stock" type="number" min="0" required />
            </div>

            <div>
              <label for="price">Harga</label>
              <input id="price" v-model.number="form.price" type="number" min="0" step="0.01" required />
            </div>
          </div>

          <label for="description">Deskripsi</label>
          <textarea id="description" v-model="form.description" rows="3"></textarea>

          <div class="actions">
            <button type="submit" :disabled="saving">
              {{ saving ? "Menyimpan..." : editingId !== null ? "Simpan Perubahan" : "Tambah Barang" }}
            </button>

            <button v-if="editingId !== null" type="button" class="secondary" @click="resetForm">Batal</button>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="list-header">
          <h2>Daftar Barang</h2>
          <button class="secondary" @click="fetchItems">Muat Ulang</button>
        </div>

        <p v-if="items.length === 0" class="muted">Belum ada barang. Tambahkan barang melalui formulir di atas.</p>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Harga</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id">
                <td>{{ item.name }}</td>
                <td>{{ item.category }}</td>
                <td>{{ item.stock }}</td>
                <td>{{ Number(item.price).toLocaleString("id-ID") }}</td>
                <td>{{ item.description || "-" }}</td>
                <td>
                  <div class="actions">
                    <button class="small secondary" @click="editItem(item)">Edit</button>
                    <button class="small danger" @click="deleteItem(item)">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>

    <p v-if="errorMessage" class="notice error">{{ errorMessage }}</p>
    <p v-if="successMessage" class="notice success">{{ successMessage }}</p>
  </main>
</template>

<style>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: Arial, sans-serif;
  color: #172033;
  background: #f1f5f9;
}

button,
input,
textarea {
  font: inherit;
}

.page {
  min-height: 100vh;
  padding: 32px 18px;
}

.login-card,
.dashboard {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
}

.login-card {
  max-width: 400px;
  margin-top: 8vh;
  padding: 30px;
  border-radius: 14px;
  background: white;
  box-shadow: 0 8px 30px #0f172a12;
}

h1 {
  margin-top: 0;
}

h2 {
  margin-top: 0;
  font-size: 20px;
}

.muted {
  color: #64748b;
}

.header,
.list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.panel {
  margin-top: 24px;
  padding: 24px;
  border-radius: 14px;
  background: white;
  box-shadow: 0 4px 18px #0f172a0a;
}

form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

label {
  font-weight: 600;
  font-size: 14px;
}

input,
textarea {
  width: 100%;
  padding: 11px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
}

button {
  padding: 11px 15px;
  border: 0;
  border-radius: 7px;
  background: #2563eb;
  color: white;
  font-weight: 600;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.secondary {
  background: #e2e8f0;
  color: #334155;
}

.danger {
  background: #dc2626;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-grid > div {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.small {
  padding: 7px 10px;
  font-size: 13px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

th,
td {
  padding: 12px 10px;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
}

th {
  color: #475569;
  font-size: 13px;
}

.notice {
  max-width: 1100px;
  margin: 18px auto 0;
  overflow-wrap: anywhere;
}

.error {
  color: #b91c1c;
}

.success {
  color: #15803d;
}

@media (max-width: 600px) {
  .page {
    padding: 18px 10px;
  }

  .panel {
    padding: 16px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .header {
    align-items: flex-start;
  }
}
</style>
