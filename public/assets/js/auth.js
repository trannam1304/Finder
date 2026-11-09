/* ============================================================
   AUTH.JS — Quản lý Xác thực Thành viên bằng JWT & HttpOnly Cookies
   Đảm bảo không lưu refresh token vào LocalStorage (Chống XSS)
   ============================================================ */

class AuthManager {
  constructor() {
    this.currentUser = null;
    this.isInitialized = false;
    this._initPromise = null;
  }

  /**
   * Khởi động và kiểm tra phiên đăng nhập từ HttpOnly Cookie
   */
  async init() {
    if (this._initPromise) return this._initPromise;

    this._initPromise = (async () => {
      try {
        await this.fetchMe();
      } catch (err) {
        this.currentUser = null;
      } finally {
        this.isInitialized = true;
        this.updateHeaderUI();
      }
      return this.currentUser;
    })();

    return this._initPromise;
  }

  isLoggedIn() {
    return Boolean(this.currentUser && this.currentUser.id);
  }

  getUser() {
    return this.currentUser;
  }

  /**
   * Gọi API lấy thông tin người dùng hiện tại
   */
  async fetchMe() {
    try {
      const res = await fetch('/api/auth/me.php', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin', // Gửi cookie access_token / refresh_token
      });

      if (res.status === 401) {
        // Access token có thể đã hết hạn, thử refresh token
        const refreshed = await this.refreshToken();
        if (refreshed) {
          return this.fetchMe();
        }
        this.currentUser = null;
        return null;
      }

      const json = await res.json();
      if (json.success && json.data?.user) {
        this.currentUser = json.data.user;
        window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: this.currentUser } }));
        return this.currentUser;
      }

      this.currentUser = null;
      return null;
    } catch (e) {
      this.currentUser = null;
      return null;
    }
  }

  /**
   * Đăng nhập thành viên
   */
  async login(identifier, password) {
    try {
      const res = await fetch('/api/auth/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ identifier, password }),
      });

      const json = await res.json();
      if (!res.ok || !json.success) {
        throw new Error(json.error?.message || 'Đăng nhập không thành công');
      }

      this.currentUser = json.data.user;
      this.updateHeaderUI();
      window.dispatchEvent(new CustomEvent('auth:login', { detail: { user: this.currentUser } }));
      window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: this.currentUser } }));
      return { success: true, data: json.data };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  /**
   * Đăng ký tài khoản thành viên mới
   */
  async register({ username, email, password, full_name }) {
    try {
      const res = await fetch('/api/auth/register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ username, email, password, full_name }),
      });

      const json = await res.json();
      if (!res.ok || !json.success) {
        throw new Error(json.error?.message || 'Đăng ký không thành công');
      }

      this.currentUser = json.data.user;
      this.updateHeaderUI();
      window.dispatchEvent(new CustomEvent('auth:login', { detail: { user: this.currentUser } }));
      window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: this.currentUser } }));
      return { success: true, data: json.data };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  /**
   * Yêu cầu mã xác nhận quên mật khẩu (OTP 6 số)
   */
  async forgotPassword(identifier) {
    try {
      const res = await fetch('/api/auth/forgot_password.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ identifier }),
      });

      const json = await res.json();
      if (!res.ok || !json.success) {
        throw new Error(json.error?.message || 'Không thể tạo mã xác nhận');
      }

      return { success: true, data: json.data, meta: json.meta };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  /**
   * Đặt lại mật khẩu với mã OTP 6 số
   */
  async resetPassword({ identifier, reset_code, new_password }) {
    try {
      const res = await fetch('/api/auth/reset_password.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ identifier, reset_code, new_password }),
      });

      const json = await res.json();
      if (!res.ok || !json.success) {
        throw new Error(json.error?.message || 'Không thể đặt lại mật khẩu');
      }

      this.currentUser = json.data.user;
      this.updateHeaderUI();
      window.dispatchEvent(new CustomEvent('auth:login', { detail: { user: this.currentUser } }));
      window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: this.currentUser } }));
      return { success: true, data: json.data };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }

  /**
   * Làm mới Access Token bằng Refresh Token trong Cookie
   */
  async refreshToken() {
    try {
      const res = await fetch('/api/auth/refresh.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      if (!res.ok) return false;
      const json = await res.json();
      if (json.success && json.data?.user) {
        this.currentUser = json.data.user;
        return true;
      }
      return false;
    } catch {
      return false;
    }
  }

  /**
   * Đăng xuất thành viên
   */
  async logout() {
    try {
      await fetch('/api/auth/logout.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
    } catch (e) {
      console.warn('Lỗi khi gọi API logout', e);
    } finally {
      this.currentUser = null;
      this.updateHeaderUI();
      window.dispatchEvent(new CustomEvent('auth:logout'));
      window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: null } }));
    }
  }

  /**
   * Tự động cập nhật giao diện User trên Header của các trang web
   */
  updateHeaderUI() {
    const container = document.getElementById('user-nav-container');
    if (!container) return;

    if (this.currentUser) {
      // Đã đăng nhập: Hiển thị Avatar + Tên + Dropdown
      const initial = (this.currentUser.full_name || this.currentUser.username || 'U')
        .trim().charAt(0).toUpperCase();

      container.innerHTML = `
        <div class="user-dropdown-wrapper" style="position:relative">
          <button type="button" class="user-profile-btn" id="btn-user-toggle-menu" aria-expanded="false" title="Menu thành viên">
            <span class="user-avatar-badge">${initial}</span>
            <span class="user-name-text">${this.escapeHtml(this.currentUser.full_name || this.currentUser.username)}</span>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>

          <div class="user-dropdown-menu" id="user-menu-dropdown" hidden>
            <div class="user-menu-header">
              <div class="user-menu-header__name">${this.escapeHtml(this.currentUser.full_name)}</div>
              <div class="user-menu-header__email">${this.escapeHtml(this.currentUser.email)}</div>
              <span class="user-menu-header__badge">${this.currentUser.role === 'admin' ? 'Quản trị viên' : 'Thành viên'}</span>
            </div>
            <a href="favorites.html" class="user-menu-item">
              <span>❤️ Quán ăn đã lưu</span>
            </a>
            <a href="suggest.html" class="user-menu-item">
              <span>✍️ Đề xuất quán mới</span>
            </a>
            ${this.currentUser.role === 'admin' ? '<a href="admin/dashboard.php" class="user-menu-item" style="color:var(--c-primary);font-weight:700"><span>⚙️ Trang quản trị</span></a>' : ''}
            <button type="button" class="user-menu-item user-menu-item--logout" id="btn-auth-logout">
              <span>🚪 Đăng xuất</span>
            </button>
          </div>
        </div>
      `;

      // Gắn sự kiện mở dropdown
      const btnToggle = container.querySelector('#btn-user-toggle-menu');
      const menu = container.querySelector('#user-menu-dropdown');
      const btnLogout = container.querySelector('#btn-auth-logout');

      if (btnToggle && menu) {
        btnToggle.addEventListener('click', (e) => {
          e.stopPropagation();
          const isHidden = menu.hidden;
          menu.hidden = !isHidden;
          btnToggle.setAttribute('aria-expanded', String(isHidden));
        });

        document.addEventListener('click', (e) => {
          if (!container.contains(e.target)) {
            menu.hidden = true;
            btnToggle.setAttribute('aria-expanded', 'false');
          }
        });
      }

      if (btnLogout) {
        btnLogout.addEventListener('click', async () => {
          await this.logout();
          window.location.reload();
        });
      }
    } else {
      // Khách vãng lai: Hiển thị nút Đăng nhập / Đăng ký
      container.innerHTML = `
        <div class="user-nav__guest-group">
          <a href="login.html" class="btn btn--ghost btn--sm" id="btn-header-login" style="text-decoration:none" title="Đăng nhập thành viên">
            Đăng nhập
          </a>
          <a href="login.html?tab=register" class="btn btn--primary btn--sm" id="btn-header-register" style="text-decoration:none" title="Đăng ký tài khoản mới">
            Đăng ký
          </a>
        </div>
      `;
    }
  }

  escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, (m) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[m]));
  }
}

export const auth = new AuthManager();

// Tự động khởi chạy khi script được tải
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => auth.init());
  } else {
    auth.init();
  }
}
