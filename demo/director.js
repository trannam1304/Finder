/**
 * Food Finder — Onboarding Video Director Helper
 * Provides non-intrusive 4-panel blurred spotlight, Neo-Brutalist tooltips,
 * smooth camera zoom math, custom animated cursor with click ripples,
 * transition curtain, and millisecond-accurate SFX event logging.
 */
(function (globalScope) {
  'use strict';

  const VIEW_W = 1920;
  const VIEW_H = 1080;

  function clamp(val, min, max) {
    return Math.max(min, Math.min(max, val));
  }

  function installBrowserDirector() {
    if (typeof window === 'undefined' || typeof document === 'undefined') return null;
    if (window.__FFDirector && document.getElementById('ff-demo-overlay')) {
      return window.__FFDirector;
    }

    // Ensure Be Vietnam Pro font link is present if page didn't load it
    if (!document.querySelector('link[href*="Be+Vietnam+Pro"]')) {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap';
      document.head.appendChild(link);
    }

    // Inject scoped styles onto documentElement
    let styleEl = document.getElementById('ff-demo-style');
    if (!styleEl) {
      styleEl = document.createElement('style');
      styleEl.id = 'ff-demo-style';
      styleEl.textContent = `
        #ff-demo-overlay {
          position: fixed;
          inset: 0;
          width: 1920px;
          height: 1080px;
          z-index: 2147483640;
          pointer-events: none;
          font-family: "Be Vietnam Pro", "Plus Jakarta Sans", system-ui, -apple-system, sans-serif;
          overflow: hidden;
        }
        .ff-dim {
          position: fixed;
          background: rgba(15, 23, 20, 0.56);
          backdrop-filter: blur(3.5px);
          -webkit-backdrop-filter: blur(3.5px);
          opacity: 0;
          transition:
            top 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            left 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            width 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            height 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            opacity 0.32s ease;
        }
        .ff-dim.is-visible {
          opacity: 1;
        }
        #ff-spot-ring {
          position: fixed;
          border: 3.5px solid #F2B01B;
          border-radius: 12px;
          box-shadow:
            0 0 0 3px #15201B,
            0 0 0 7px rgba(242, 176, 27, 0.34),
            0 12px 36px rgba(21, 32, 27, 0.35);
          opacity: 0;
          transition:
            top 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            left 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            width 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            height 0.52s cubic-bezier(0.22, 1, 0.36, 1),
            opacity 0.3s ease;
        }
        #ff-spot-ring.is-visible {
          opacity: 1;
        }
        #ff-tooltip {
          position: fixed;
          width: max-content;
          max-width: 470px;
          min-width: 310px;
          background: #FFFDF9;
          color: #15201B;
          border: 3px solid #15201B;
          border-radius: 14px;
          box-shadow: 7px 7px 0 #15201B;
          padding: 18px 22px;
          opacity: 0;
          transform: translateY(10px) scale(0.97);
          transition:
            top 0.44s cubic-bezier(0.22, 1, 0.36, 1),
            left 0.44s cubic-bezier(0.22, 1, 0.36, 1),
            opacity 0.28s ease,
            transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
          z-index: 2147483644;
        }
        #ff-tooltip.is-visible {
          opacity: 1;
          transform: translateY(0) scale(1);
        }
        .ff-tt-badge {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          padding: 4px 11px;
          border-radius: 6px;
          background: #F2B01B;
          color: #15201B;
          border: 2px solid #15201B;
          font-size: 12px;
          font-weight: 800;
          letter-spacing: 0.04em;
          text-transform: uppercase;
          margin-bottom: 10px;
        }
        .ff-tt-badge--red {
          background: #D22B1F;
          color: #FFFFFF;
        }
        .ff-tt-title {
          font-size: 20px;
          font-weight: 800;
          line-height: 1.3;
          color: #15201B;
          margin: 0 0 6px 0;
        }
        .ff-tt-body {
          font-size: 15px;
          font-weight: 500;
          line-height: 1.5;
          color: #36453D;
          margin: 0;
        }
        .ff-tt-pills {
          display: flex;
          flex-wrap: wrap;
          gap: 8px;
          margin-top: 12px;
        }
        .ff-tt-pill {
          padding: 4px 11px;
          border-radius: 999px;
          border: 2px solid #15201B;
          background: #F1F3F0;
          color: #15201B;
          font-size: 13px;
          font-weight: 700;
          transition: all 0.22s ease;
        }
        .ff-tt-pill.is-active {
          background: #D22B1F;
          color: #FFFFFF;
          transform: translateY(-1px);
          box-shadow: 2px 2px 0 #15201B;
        }
        #ff-progress {
          position: fixed;
          left: 24px;
          bottom: 20px;
          z-index: 2147483645;
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 8px 14px;
          background: rgba(21, 32, 27, 0.9);
          color: #FFFFFF;
          border: 2px solid #F2B01B;
          border-radius: 999px;
          box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
          opacity: 0;
          transition: opacity 0.3s ease, top 0.35s ease, bottom 0.35s ease, left 0.35s ease;
        }
        #ff-progress.is-visible {
          opacity: 1;
        }
        .ff-prog-dot {
          width: 9px;
          height: 9px;
          border-radius: 50%;
          background: #F2B01B;
          flex-shrink: 0;
        }
        .ff-prog-label {
          font-size: 13px;
          font-weight: 700;
          letter-spacing: 0.01em;
          white-space: nowrap;
        }
        .ff-prog-track {
          width: 110px;
          height: 6px;
          border-radius: 999px;
          background: rgba(255, 255, 255, 0.22);
          overflow: hidden;
        }
        .ff-prog-fill {
          height: 100%;
          width: 0%;
          border-radius: 999px;
          background: #F2B01B;
          transition: width 0.45s ease;
        }
        #ff-cursor {
          position: fixed;
          left: 960px;
          top: 540px;
          width: 34px;
          height: 34px;
          margin-left: -4px;
          margin-top: -3px;
          z-index: 2147483646;
          opacity: 0;
          transform-origin: 4px 3px;
          transition: opacity 0.2s ease, transform 0.1s ease;
          filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.35));
        }
        #ff-cursor.is-visible {
          opacity: 1;
        }
        #ff-cursor.is-pressed {
          transform: scale(0.84);
        }
        .ff-ripple {
          position: fixed;
          width: 20px;
          height: 20px;
          margin-left: -10px;
          margin-top: -10px;
          border-radius: 50%;
          border: 4px solid #F2B01B;
          background: rgba(210, 43, 31, 0.22);
          z-index: 2147483645;
          pointer-events: none;
          animation: ffRippleOut 0.56s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes ffRippleOut {
          0% { transform: scale(0.4); opacity: 1; }
          100% { transform: scale(3.1); opacity: 0; }
        }
        #ff-curtain {
          position: fixed;
          inset: 0;
          background: #000000;
          opacity: 0;
          z-index: 2147483647;
          transition: opacity 0.24s ease;
        }
        #ff-curtain.is-closed {
          opacity: 1;
        }
      `;
      document.documentElement.appendChild(styleEl);
    }

    // Mount overlay root directly under <html> so body CSS transform never affects overlay coordinates
    const root = document.createElement('div');
    root.id = 'ff-demo-overlay';
    root.innerHTML = `
      <div class="ff-dim" id="ff-dim-top"></div>
      <div class="ff-dim" id="ff-dim-bottom"></div>
      <div class="ff-dim" id="ff-dim-left"></div>
      <div class="ff-dim" id="ff-dim-right"></div>
      <div id="ff-spot-ring"></div>
      <div id="ff-tooltip">
        <div class="ff-tt-badge" id="ff-tt-badge"></div>
        <h3 class="ff-tt-title" id="ff-tt-title"></h3>
        <p class="ff-tt-body" id="ff-tt-body"></p>
        <div class="ff-tt-pills" id="ff-tt-pills" style="display:none"></div>
      </div>
      <div id="ff-progress">
        <span class="ff-prog-dot"></span>
        <span class="ff-prog-label" id="ff-prog-label">Food Finder</span>
        <div class="ff-prog-track"><div class="ff-prog-fill" id="ff-prog-fill"></div></div>
      </div>
      <div id="ff-cursor">
        <svg viewBox="0 0 32 32" width="34" height="34" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M5 3L25.5 14.2L15.8 17.1L12.6 26.8L5 3Z" fill="#FFFFFF" stroke="#15201B" stroke-width="2.6" stroke-linejoin="round"/>
          <circle cx="6.2" cy="4.2" r="2.2" fill="#D22B1F"/>
        </svg>
      </div>
      <div id="ff-curtain"></div>
    `;
    document.documentElement.appendChild(root);

    const dimTop = document.getElementById('ff-dim-top');
    const dimBottom = document.getElementById('ff-dim-bottom');
    const dimLeft = document.getElementById('ff-dim-left');
    const dimRight = document.getElementById('ff-dim-right');
    const spotRing = document.getElementById('ff-spot-ring');
    const tooltip = document.getElementById('ff-tooltip');
    const ttBadge = document.getElementById('ff-tt-badge');
    const ttTitle = document.getElementById('ff-tt-title');
    const ttBody = document.getElementById('ff-tt-body');
    const ttPills = document.getElementById('ff-tt-pills');
    const progEl = document.getElementById('ff-progress');
    const progLabel = document.getElementById('ff-prog-label');
    const progFill = document.getElementById('ff-prog-fill');
    const cursorEl = document.getElementById('ff-cursor');
    const curtainEl = document.getElementById('ff-curtain');

    let t0 = performance.now();
    let events = [];
    let zoomState = { scale: 1, tx: 0, ty: 0 };
    let cursorPos = { x: 960, y: 540 };
    let spotlightActive = false;

    const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

    const director = {
      resetTimer() {
        t0 = performance.now();
        events = [];
        return true;
      },

      logEvent(type) {
        events.push({ type, timeMs: Math.round(performance.now() - t0) });
      },

      getEvents() {
        return {
          events: events.slice(),
          elapsedMs: Math.round(performance.now() - t0),
        };
      },

      async curtainIn(ms = 220) {
        curtainEl.classList.add('is-closed');
        await sleep(ms);
      },

      async curtainOut(ms = 240) {
        curtainEl.classList.remove('is-closed');
        await sleep(ms);
      },

      setCurtainImmediate(closed) {
        curtainEl.style.transition = 'none';
        curtainEl.classList.toggle('is-closed', !!closed);
        void curtainEl.offsetHeight;
        curtainEl.style.transition = '';
      },

      setProgress(label, pct, positionMode = 'auto') {
        progLabel.textContent = label || 'Food Finder';
        progFill.style.width = `${clamp(pct || 0, 0, 100)}%`;
        progEl.classList.add('is-visible');
        if (positionMode === 'top-right') {
          progEl.style.bottom = 'auto';
          progEl.style.left = 'auto';
          progEl.style.top = '20px';
          progEl.style.right = '24px';
        } else {
          progEl.style.top = 'auto';
          progEl.style.right = 'auto';
          progEl.style.bottom = '20px';
          progEl.style.left = '24px';
        }
      },

      hideProgress() {
        progEl.classList.remove('is-visible');
      },

      getUnionRect(selectors) {
        const list = Array.isArray(selectors) ? selectors : [selectors];
        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        let found = false;
        for (const sel of list) {
          const el = typeof sel === 'string' ? document.querySelector(sel) : sel;
          if (!el) continue;
          const r = el.getBoundingClientRect();
          if (r.width === 0 && r.height === 0) continue;
          found = true;
          minX = Math.min(minX, r.left);
          minY = Math.min(minY, r.top);
          maxX = Math.max(maxX, r.right);
          maxY = Math.max(maxY, r.bottom);
        }
        if (!found) return null;
        return { x: minX, y: minY, width: maxX - minX, height: maxY - minY };
      },

      toUnzoomedRect(viewportRect) {
        if (!viewportRect) return null;
        const { scale, tx, ty } = zoomState;
        return {
          x: (viewportRect.x - tx) / scale,
          y: (viewportRect.y - ty) / scale,
          width: viewportRect.width / scale,
          height: viewportRect.height / scale,
        };
      },

      prepareCamera() {
        document.documentElement.style.overflow = 'hidden';
        document.documentElement.style.background = '#15201B';
        document.body.style.transformOrigin = '0 0';
        document.body.style.transition = 'transform 0.65s cubic-bezier(0.22, 1, 0.36, 1)';
      },

      zoomToRect(unzoomedRect, scale = 1.45, targetX = VIEW_W / 2, targetY = VIEW_H / 2) {
        director.prepareCamera();
        const cx = unzoomedRect.x + unzoomedRect.width / 2;
        const cy = unzoomedRect.y + unzoomedRect.height / 2;
        const tx = clamp(targetX - cx * scale, VIEW_W * (1 - scale), 0);
        const ty = clamp(targetY - cy * scale, VIEW_H * (1 - scale), 0);
        zoomState = { scale, tx, ty };
        document.body.style.transform = `translate(${tx.toFixed(1)}px, ${ty.toFixed(1)}px) scale(${scale})`;
        director.logEvent('whoosh');
        return {
          tx,
          ty,
          scale,
          zoomedRect: {
            x: unzoomedRect.x * scale + tx,
            y: unzoomedRect.y * scale + ty,
            width: unzoomedRect.width * scale,
            height: unzoomedRect.height * scale,
          },
        };
      },

      zoomReset() {
        director.prepareCamera();
        const wasZoomed = Math.abs(zoomState.scale - 1) > 0.02;
        zoomState = { scale: 1, tx: 0, ty: 0 };
        document.body.style.transform = 'translate(0px, 0px) scale(1)';
        if (wasZoomed) director.logEvent('whoosh');
        return { tx: 0, ty: 0, scale: 1 };
      },

      spotlightRect(rect, padding = 10) {
        if (!rect) return;
        const x = clamp(Math.round(rect.x - padding), 0, VIEW_W);
        const y = clamp(Math.round(rect.y - padding), 0, VIEW_H);
        const right = clamp(Math.round(rect.x + rect.width + padding), 0, VIEW_W);
        const bottom = clamp(Math.round(rect.y + rect.height + padding), 0, VIEW_H);
        const w = Math.max(0, right - x);
        const h = Math.max(0, bottom - y);

        dimTop.style.top = '0px';
        dimTop.style.left = '0px';
        dimTop.style.width = `${VIEW_W}px`;
        dimTop.style.height = `${y}px`;

        dimBottom.style.top = `${bottom}px`;
        dimBottom.style.left = '0px';
        dimBottom.style.width = `${VIEW_W}px`;
        dimBottom.style.height = `${Math.max(0, VIEW_H - bottom)}px`;

        dimLeft.style.top = `${y}px`;
        dimLeft.style.left = '0px';
        dimLeft.style.width = `${x}px`;
        dimLeft.style.height = `${h}px`;

        dimRight.style.top = `${y}px`;
        dimRight.style.left = `${right}px`;
        dimRight.style.width = `${Math.max(0, VIEW_W - right)}px`;
        dimRight.style.height = `${h}px`;

        spotRing.style.top = `${y}px`;
        spotRing.style.left = `${x}px`;
        spotRing.style.width = `${w}px`;
        spotRing.style.height = `${h}px`;

        [dimTop, dimBottom, dimLeft, dimRight, spotRing].forEach((el) => el.classList.add('is-visible'));
        spotlightActive = true;

        // Auto-avoid bottom-left progress pill if spotlight touches bottom-left
        if (x < 340 && bottom > VIEW_H - 95) {
          progEl.style.bottom = 'auto';
          progEl.style.top = '18px';
          progEl.style.left = 'auto';
          progEl.style.right = '24px';
        }
        return { x, y, width: w, height: h };
      },

      clearSpotlight() {
        [dimTop, dimBottom, dimLeft, dimRight, spotRing].forEach((el) => el.classList.remove('is-visible'));
        spotlightActive = false;
      },

      showTooltip({ badge = '', badgeRed = false, title = '', body = '', anchorRect = null, side = 'bottom', pills = null, activePill = null }) {
        ttBadge.textContent = badge;
        ttBadge.style.display = badge ? 'inline-flex' : 'none';
        ttBadge.classList.toggle('ff-tt-badge--red', !!badgeRed);
        ttTitle.textContent = title;
        ttBody.textContent = body;

        if (Array.isArray(pills) && pills.length > 0) {
          ttPills.style.display = 'flex';
          ttPills.innerHTML = '';
          pills.forEach((label) => {
            const span = document.createElement('span');
            span.className = 'ff-tt-pill' + (activePill === label ? ' is-active' : '');
            span.dataset.pill = label;
            span.textContent = label;
            ttPills.appendChild(span);
          });
        } else {
          ttPills.style.display = 'none';
          ttPills.innerHTML = '';
        }

        // Measure intrinsic tooltip size off-flow
        tooltip.style.left = '0px';
        tooltip.style.top = '0px';
        const ttRect = tooltip.getBoundingClientRect();
        const ttW = Math.min(Math.max(ttRect.width || 420, 310), 470);
        const ttH = ttRect.height || 145;
        const margin = 24;
        const pad = 28;

        let left = (VIEW_W - ttW) / 2;
        let top = (VIEW_H - ttH) / 2;

        if (anchorRect) {
          const trySide = (s) => {
            if (s === 'bottom') {
              return {
                x: anchorRect.x + anchorRect.width / 2 - ttW / 2,
                y: anchorRect.y + anchorRect.height + margin,
              };
            }
            if (s === 'top') {
              return {
                x: anchorRect.x + anchorRect.width / 2 - ttW / 2,
                y: anchorRect.y - ttH - margin,
              };
            }
            if (s === 'right') {
              return {
                x: anchorRect.x + anchorRect.width + margin,
                y: anchorRect.y + anchorRect.height / 2 - ttH / 2,
              };
            }
            if (s === 'left') {
              return {
                x: anchorRect.x - ttW - margin,
                y: anchorRect.y + anchorRect.height / 2 - ttH / 2,
              };
            }
            return { x: left, y: top };
          };

          const order = [side, 'bottom', 'top', 'right', 'left'];
          let chosen = null;
          for (const s of order) {
            const c = trySide(s);
            if (c.x >= pad && c.x + ttW <= VIEW_W - pad && c.y >= pad && c.y + ttH <= VIEW_H - pad) {
              chosen = c;
              break;
            }
          }
          if (!chosen) chosen = trySide(side);
          left = clamp(chosen.x, pad, VIEW_W - ttW - pad);
          top = clamp(chosen.y, pad, VIEW_H - ttH - pad);
        }

        tooltip.style.left = `${Math.round(left)}px`;
        tooltip.style.top = `${Math.round(top)}px`;
        tooltip.classList.add('is-visible');
        return { left, top, width: ttW, height: ttH };
      },

      updateTooltipPills(activePills) {
        const activeSet = new Set(Array.isArray(activePills) ? activePills : [activePills]);
        ttPills.querySelectorAll('.ff-tt-pill').forEach((el) => {
          el.classList.toggle('is-active', activeSet.has(el.dataset.pill));
        });
      },

      hideTooltip() {
        tooltip.classList.remove('is-visible');
      },

      setCursorPos(x, y, visible = true) {
        cursorPos = { x, y };
        cursorEl.style.left = `${Math.round(x)}px`;
        cursorEl.style.top = `${Math.round(y)}px`;
        cursorEl.classList.toggle('is-visible', !!visible);
      },

      moveCursorTo(targetX, targetY, durationMs = 450) {
        cursorEl.classList.add('is-visible');
        const startX = cursorPos.x;
        const startY = cursorPos.y;
        const startTime = performance.now();
        const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

        return new Promise((resolve) => {
          function step(now) {
            const p = clamp((now - startTime) / Math.max(durationMs, 16), 0, 1);
            const e = ease(p);
            const cx = startX + (targetX - startX) * e;
            const cy = startY + (targetY - startY) * e;
            cursorPos = { x: cx, y: cy };
            cursorEl.style.left = `${cx.toFixed(1)}px`;
            cursorEl.style.top = `${cy.toFixed(1)}px`;
            if (p < 1) {
              requestAnimationFrame(step);
            } else {
              resolve(cursorPos);
            }
          }
          requestAnimationFrame(step);
        });
      },

      async clickVisual(x = cursorPos.x, y = cursorPos.y, soundType = 'click') {
        cursorPos = { x, y };
        cursorEl.style.left = `${Math.round(x)}px`;
        cursorEl.style.top = `${Math.round(y)}px`;
        cursorEl.classList.add('is-visible', 'is-pressed');
        director.logEvent(soundType);

        const ripple = document.createElement('div');
        ripple.className = 'ff-ripple';
        ripple.style.left = `${Math.round(x)}px`;
        ripple.style.top = `${Math.round(y)}px`;
        root.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);

        await sleep(110);
        cursorEl.classList.remove('is-pressed');
        await sleep(90);
      },
    };

    window.__FFDirector = director;
    return director;
  }

  function getIntroHtml() {
    return `<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    width: 1920px; height: 1080px; overflow: hidden;
    font-family: "Be Vietnam Pro", system-ui, sans-serif;
    background: #F4F5F2; color: #15201B;
  }
  .stage {
    position: relative; width: 1920px; height: 1080px;
    background:
      radial-gradient(circle at 12% 18%, rgba(242, 176, 27, 0.22), transparent 36%),
      radial-gradient(circle at 88% 82%, rgba(210, 43, 31, 0.16), transparent 40%),
      linear-gradient(135deg, #F8F8F5 0%, #ECEFE9 100%);
    display: flex; align-items: center; justify-content: center;
  }
  .grid-bg {
    position: absolute; inset: 0;
    background-image:
      linear-gradient(to right, rgba(21,32,27,0.05) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(21,32,27,0.05) 1px, transparent 1px);
    background-size: 64px 64px;
  }
  .beat {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 80px 140px; text-align: center;
    opacity: 0; transform: translateY(22px) scale(0.98);
    transition: opacity 0.6s ease, transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
    pointer-events: none;
  }
  .beat.is-active {
    opacity: 1; transform: translateY(0) scale(1);
  }
  .pill {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 10px 22px; border-radius: 999px;
    border: 3px solid #15201B; background: #F2B01B;
    font-size: 18px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase;
    box-shadow: 5px 5px 0 #15201B; margin-bottom: 34px;
  }
  .pill--red { background: #D22B1F; color: #FFFFFF; }
  h1 {
    font-size: 82px; font-weight: 900; line-height: 1.15; letter-spacing: -0.02em;
    margin-bottom: 24px; color: #15201B;
  }
  .accent { color: #D22B1F; }
  .sub {
    font-size: 28px; font-weight: 500; color: #46544C;
    max-width: 1120px; line-height: 1.5; margin-bottom: 52px;
  }
  .cards {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; width: 100%; max-width: 1460px;
  }
  .card {
    background: #FFFFFF; border: 3px solid #15201B; border-radius: 18px;
    padding: 32px 30px; text-align: left;
    box-shadow: 8px 8px 0 #15201B;
  }
  .card-num {
    display: inline-flex; width: 44px; height: 44px; border-radius: 10px;
    align-items: center; justify-content: center;
    background: #FBE7E4; color: #D22B1F; border: 2px solid #15201B;
    font-size: 20px; font-weight: 900; margin-bottom: 18px;
  }
  .card-num--gold { background: #FFF4D6; color: #15201B; }
  .card h3 { font-size: 24px; font-weight: 800; margin-bottom: 10px; color: #15201B; }
  .card p { font-size: 19px; line-height: 1.5; color: #46544C; }
  .brand-row {
    display: flex; align-items: center; gap: 24px; margin-bottom: 24px;
  }
  .brand-title { font-size: 92px; font-weight: 900; color: #15201B; letter-spacing: -0.025em; }
  .url-chip {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 12px 26px; background: #15201B; color: #F2B01B;
    border-radius: 12px; font-size: 22px; font-weight: 700; margin-bottom: 48px;
  }
  .fade-veil {
    position: absolute; inset: 0; background: #15201B; opacity: 0;
    transition: opacity 0.4s ease; pointer-events: none; z-index: 20;
  }
  .fade-veil.is-closed { opacity: 1; }
</style>
</head>
<body>
<div class="stage">
  <div class="grid-bg"></div>

  <div class="beat is-active" id="beat-1">
    <div class="pill">BÀI TOÁN THỰC TẾ HẰNG NGÀY</div>
    <h1>“Hôm nay <span class="accent">ăn gì</span>? Ăn ở đâu <span class="accent">gần đây</span>?”</h1>
    <p class="sub">Sinh viên &amp; giới trẻ thường mất nhiều thời gian phân vân chọn quán ăn phù hợp vị trí, túi tiền và lịch trình di chuyển.</p>
    <div class="cards">
      <div class="card">
        <div class="card-num">01</div>
        <h3>Quá nhiều lựa chọn</h3>
        <p>Mất 15–30 phút suy nghĩ xem nên ăn cơm, bún, phở, bánh mì hay lẩu nướng.</p>
      </div>
      <div class="card">
        <div class="card-num">02</div>
        <h3>Khó lọc theo nhu cầu</h3>
        <p>Cần biết ngay quán nào gần bán kính đi bộ, đúng mức giá và đang mở cửa.</p>
      </div>
      <div class="card">
        <div class="card-num">03</div>
        <h3>Ngại đi ngược đường</h3>
        <p>Muốn kết hợp ăn uống – tham quan – vui chơi thành một lộ trình hợp lý.</p>
      </div>
    </div>
  </div>

  <div class="beat" id="beat-2">
    <div class="pill pill--red">ĐỒ ÁN MÔN HỌC • ĐH TÔN ĐỨC THẮNG</div>
    <div class="brand-row">
      <svg width="96" height="96" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="16" cy="16" r="14" fill="#D22B1F" stroke="#15201B" stroke-width="1.5"/>
        <path d="M10 20c0-4 2-8 6-8s6 4 6 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
        <circle cx="16" cy="11" r="2" fill="#fff"/>
        <path d="M9 22h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <div class="brand-title">Food Finder</div>
    </div>
    <p class="sub" style="margin-bottom: 24px;">Tìm quán ăn quanh bạn — Bản đồ ẩm thực, điểm tham quan &amp; gợi ý lộ trình thông minh</p>
    <div class="url-chip">https://finder-theta-rose.vercel.app/</div>
    <div class="cards">
      <div class="card">
        <div class="card-num card-num--gold">01</div>
        <h3>Tìm quán gần bạn</h3>
        <p>Định vị trực quan trên bản đồ số kèm bộ lọc khoảng cách 500 m – 15 km &amp; mức giá.</p>
      </div>
      <div class="card">
        <div class="card-num card-num--gold">02</div>
        <h3>“Hôm nay ăn gì?”</h3>
        <p>Gợi ý ngẫu nhiên có trọng số, mở nhanh chỉ đường Google Maps chỉ với 1 chạm.</p>
      </div>
      <div class="card">
        <div class="card-num card-num--gold">03</div>
        <h3>Lập lộ trình tự động</h3>
        <p>Smart Generator thiết kế lịch trình đi chơi đa điểm theo phong cách &amp; thời lượng.</p>
      </div>
    </div>
  </div>

  <div class="fade-veil" id="veil"></div>
</div>
</body>
</html>`;
  }

  function getOutroHtml() {
    return `<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    width: 1920px; height: 1080px; overflow: hidden;
    font-family: "Be Vietnam Pro", system-ui, sans-serif;
    background: #15201B; color: #FFFFFF;
  }
  .stage {
    position: relative; width: 1920px; height: 1080px;
    background:
      radial-gradient(circle at 20% 20%, rgba(210, 43, 31, 0.30), transparent 44%),
      radial-gradient(circle at 82% 78%, rgba(242, 176, 27, 0.25), transparent 45%),
      linear-gradient(145deg, #15201B 0%, #1E2D26 100%);
    display: flex; align-items: center; justify-content: center;
  }
  .beat {
    position: absolute; inset: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 80px 140px; text-align: center;
    opacity: 0; transform: translateY(20px) scale(0.98);
    transition: opacity 0.55s ease, transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .beat.is-active {
    opacity: 1; transform: translateY(0) scale(1);
  }
  .pill {
    display: inline-flex; padding: 10px 24px; border-radius: 999px;
    background: #F2B01B; color: #15201B; border: 3px solid #FFFDF9;
    font-size: 18px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase;
    margin-bottom: 30px;
  }
  h1 { font-size: 74px; font-weight: 900; line-height: 1.18; margin-bottom: 24px; }
  .gold { color: #F2B01B; }
  .sub { font-size: 26px; color: #CBD5CE; max-width: 1120px; line-height: 1.5; margin-bottom: 48px; }
  .cards {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; width: 100%; max-width: 1460px;
  }
  .card {
    background: #FFFDF9; color: #15201B; border: 3px solid #F2B01B;
    border-radius: 18px; padding: 30px 28px; text-align: left;
    box-shadow: 8px 8px 0 #D22B1F;
  }
  .card h3 { font-size: 24px; font-weight: 800; margin-bottom: 10px; color: #D22B1F; }
  .card p { font-size: 18px; line-height: 1.5; color: #36453D; }
  .team-box {
    margin-top: 20px; padding: 22px 36px; border-radius: 16px;
    background: rgba(255, 253, 249, 0.08); border: 2px solid rgba(242, 176, 27, 0.5);
    font-size: 22px; color: #E8EDE9; line-height: 1.7;
  }
  .url-pill {
    margin-top: 28px; padding: 14px 32px; border-radius: 12px;
    background: #D22B1F; color: #FFFFFF; border: 3px solid #FFFDF9;
    font-size: 24px; font-weight: 800;
  }
  .fade-veil {
    position: absolute; inset: 0; background: #000000; opacity: 0;
    transition: opacity 0.45s ease; pointer-events: none; z-index: 20;
  }
  .fade-veil.is-closed { opacity: 1; }
</style>
</head>
<body>
<div class="stage">
  <div class="beat is-active" id="beat-1">
    <div class="pill">TỔNG KẾT GIÁ TRỊ SẢN PHẨM</div>
    <h1>Food Finder — <span class="gold">Tìm nhanh, đi chuẩn</span></h1>
    <p class="sub">Giải pháp bản đồ số giúp người dùng tìm quán ăn phù hợp và lên kế hoạch đi chơi chỉ trong vài giây.</p>
    <div class="cards">
      <div class="card">
        <h3>1. Tìm kiếm chính xác</h3>
        <p>Định vị quanh vị trí hiện tại, lọc mượt theo loại hình, bán kính 500 m – 15 km, mức giá &amp; giờ mở cửa.</p>
      </div>
      <div class="card">
        <h3>2. Quyết định tức thì</h3>
        <p>Tính năng “Hôm nay ăn gì?” chọn ngẫu nhiên thông minh kèm chỉ đường Google Maps một chạm.</p>
      </div>
      <div class="card">
        <h3>3. Lộ trình tối ưu</h3>
        <p>Smart Generator tự động sắp xếp chặng đường ẩm thực – tham quan – vui chơi liền mạch.</p>
      </div>
    </div>
  </div>

  <div class="beat" id="beat-2">
    <div class="pill">FOOD FINDER • ĐỒ ÁN MÔN HỌC</div>
    <h1>Trân trọng <span class="gold">cảm ơn Quý Thầy/Cô</span><br>đã theo dõi &amp; đánh giá!</h1>
    <div class="url-pill">https://finder-theta-rose.vercel.app/</div>
    <div class="team-box">
      <strong>Nhóm thực hiện — ĐH Tôn Đức Thắng:</strong><br>
      Phạm Nguyễn Tuấn Kiệt (52400207) &bull; Nguyễn Trọng Quý (52400230) &bull; Trần Hữu Nam (52400217)
    </div>
  </div>

  <div class="fade-veil" id="veil"></div>
</div>
</body>
</html>`;
  }

  if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    installBrowserDirector();
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
      installBrowserDirector,
      getIntroHtml,
      getOutroHtml,
    };
  } else {
    globalScope.FoodFinderDirector = {
      installBrowserDirector,
      getIntroHtml,
      getOutroHtml,
    };
  }
})(typeof window !== 'undefined' ? window : globalThis);
