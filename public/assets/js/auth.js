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
   * Helper phân tích phản hồi JSON an toàn, tránh lỗi Unexpected end of JSON input
   */
  async _parseResponse(res, fallbackMessage = 'Yêu cầu không thành công') {
    let json = null;
    let rawText = '';
    try {
      rawText = await res.text();
      json = JSON.parse(rawText);
    } catch {
      json = null;
    }

    if (!res.ok || !json?.success) {
      let msg = json?.error?.message || json?.message;
      if (!msg) {
        if (res.status === 404 || res.status === 405) {
          msg = `Lỗi ${res.status}: Endpoint API chưa sẵn sàng hoặc chưa được cấu hình routing trên máy chủ.`;
        } else if (rawText && !rawText.trim().startsWith('<') && rawText.length < 200) {
          msg = `Lỗi máy chủ (${res.status}): ${rawText.trim()}`;
        } else if (rawText && rawText.trim().startsWith('<')) {
          msg = `Lỗi máy chủ (${res.status}): Phản hồi trả về HTML thay vì dữ liệu JSON API.`;
        } else {
          msg = `Lỗi máy chủ (${res.status}): ${fallbackMessage}`;
        }
      }
      throw new Error(msg);
    }
    return json;
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

      const json = await this._parseResponse(res, 'Tên đăng nhập hoặc mật khẩu không chính xác');

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

      const json = await this._parseResponse(res, 'Đăng ký tài khoản không thành công');

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

      const json = await this._parseResponse(res, 'Không thể tạo mã xác nhận');

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

      const json = await this._parseResponse(res, 'Không thể đặt lại mật khẩu');

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
            <svg class="user-arrow-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

      // Gắn và thiết lập tương tác đóng/mở dropdown
      this.setupUserDropdown(container);
    } else {
      // Dọn dẹp listener tài liệu nếu chuyển sang chế độ khách
      if (this._onDocClick) {
        document.removeEventListener('click', this._onDocClick);
        this._onDocClick = null;
      }
      if (this._onDocKeydown) {
        document.removeEventListener('keydown', this._onDocKeydown);
        this._onDocKeydown = null;
      }

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

  /**
   * Thiết lập dropdown tương tác cho User Header:
   * - Mặc định: Ẩn (đóng) khi mới tải trang.
   * - Khi click vào nút user: Đảo trạng thái (toggle mở/đóng), xoay icon mũi tên v 180 độ.
   * - Khi click ra ngoài (Click outside): Tự động đóng dropdown menu lại.
   */
  setupUserDropdown(container) {
    const wrapper = container.querySelector('.user-dropdown-wrapper');
    const btnToggle = container.querySelector('#btn-user-toggle-menu');
    const menu = container.querySelector('#user-menu-dropdown');
    const btnLogout = container.querySelector('#btn-auth-logout');

    if (!btnToggle || !menu) return;

    // 1. Mặc định: Menu được ẩn và nút ở trạng thái đóng
    menu.hidden = true;
    menu.classList.remove('is-open');
    btnToggle.setAttribute('aria-expanded', 'false');
    btnToggle.classList.remove('is-open');
    wrapper?.classList.remove('is-open');

    const closeDropdown = () => {
      menu.hidden = true;
      menu.classList.remove('is-open');
      btnToggle.setAttribute('aria-expanded', 'false');
      btnToggle.classList.remove('is-open');
      wrapper?.classList.remove('is-open');
    };

    const openDropdown = () => {
      menu.hidden = false;
      menu.classList.add('is-open');
      btnToggle.setAttribute('aria-expanded', 'true');
      btnToggle.classList.add('is-open');
      wrapper?.classList.add('is-open');
    };

    // 2. Toggle khi click vào nút user: mở nếu đang đóng, đóng nếu đang mở
    const handleToggleClick = (e) => {
      e.stopPropagation();
      const isOpen = btnToggle.getAttribute('aria-expanded') === 'true';
      if (isOpen) {
        closeDropdown();
      } else {
        openDropdown();
      }
    };

    btnToggle.addEventListener('click', handleToggleClick);

    // 3. Click outside: Tự động đóng dropdown menu khi click ra ngoài
    if (this._onDocClick) {
      document.removeEventListener('click', this._onDocClick);
    }
    this._onDocClick = (e) => {
      if (wrapper && !wrapper.contains(e.target)) {
        closeDropdown();
      }
    };
    document.addEventListener('click', this._onDocClick);

    // Bổ sung: Bấm phím Escape để đóng dropdown
    if (this._onDocKeydown) {
      document.removeEventListener('keydown', this._onDocKeydown);
    }
    this._onDocKeydown = (e) => {
      if (e.key === 'Escape') {
        closeDropdown();
      }
    };
    document.addEventListener('keydown', this._onDocKeydown);

    // Xử lý nút Đăng xuất
    if (btnLogout) {
      btnLogout.addEventListener('click', async (e) => {
        e.preventDefault();
        closeDropdown();
        await this.logout();
        window.location.reload();
      });
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
