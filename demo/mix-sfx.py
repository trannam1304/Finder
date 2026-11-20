"""
Synthesize gentle, subtle UI sound effects (no voiceover) and mux them with
the merged 1080p Food Finder onboarding demo video.
"""

import json
import math
import os
import random
import struct
import subprocess
import wave

SAMPLE_RATE = 48000
CHANNELS = 2

SCRATCH_DIR = (
    r"C:\Users\Kiet\.gemini\antigravity\brain\7928c8fd-df85-4da1-8c9a-a5a8d0073acd"
    r"\scratch\demo-build"
)
EVENTS_PATH = os.path.join(SCRATCH_DIR, "events.json")
DURATIONS_PATH = os.path.join(SCRATCH_DIR, "clip_durations.json")
MERGED_VIDEO_PATH = os.path.join(SCRATCH_DIR, "merged_silent.mp4")
WAV_OUT_PATH = os.path.join(SCRATCH_DIR, "audio", "sfx_master.wav")
FINAL_MP4_PATH = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "food-finder-onboarding-demo.mp4",
)


def synth_click():
    """Soft tactile UI button click (48 ms)."""
    dur = 0.048
    n = int(SAMPLE_RATE * dur)
    samples = []
    phase1 = 0.0
    phase2 = 0.0
    for i in range(n):
        t = i / SAMPLE_RATE
        f1 = 1450.0 * math.exp(-t * 95.0) + 420.0
        f2 = 280.0 * math.exp(-t * 45.0) + 120.0
        phase1 += 2.0 * math.pi * f1 / SAMPLE_RATE
        phase2 += 2.0 * math.pi * f2 / SAMPLE_RATE
        atk = min(1.0, t / 0.002)
        env1 = math.exp(-t * 115.0)
        env2 = math.exp(-t * 68.0)
        val = atk * (0.14 * math.sin(phase1) * env1 + 0.16 * math.sin(phase2) * env2)
        samples.append((val, val))
    return samples


def synth_select():
    """Gentle ascending bubble pop for dropdown/filter selection (82 ms)."""
    dur = 0.082
    n = int(SAMPLE_RATE * dur)
    samples = []
    phase = 0.0
    for i in range(n):
        t = i / SAMPLE_RATE
        freq = 520.0 + 480.0 * (1.0 - math.exp(-t * 42.0))
        phase += 2.0 * math.pi * freq / SAMPLE_RATE
        atk = min(1.0, t / 0.004)
        dec = math.exp(-t * 36.0)
        val = atk * dec * 0.18 * (math.sin(phase) + 0.25 * math.sin(phase * 2.0))
        samples.append((val, val))
    return samples


def synth_whoosh():
    """Velvety soft air swell for camera zoom & spotlight transitions (260 ms)."""
    dur = 0.260
    n = int(SAMPLE_RATE * dur)
    samples = []
    rng = random.Random(42)
    lp_l = 0.0
    lp_r = 0.0
    bp_l = 0.0
    bp_r = 0.0
    sub_phase = 0.0
    for i in range(n):
        t = i / SAMPLE_RATE
        norm = t / dur
        bell = math.sin(math.pi * norm) ** 1.7
        # Simple 2-stage low/band smoothing on deterministic noise
        nl = rng.uniform(-1.0, 1.0)
        nr = rng.uniform(-1.0, 1.0)
        cutoff = 0.05 + 0.08 * bell
        lp_l += cutoff * (nl - lp_l)
        lp_r += cutoff * (nr - lp_r)
        bp_l += 0.22 * (lp_l - bp_l)
        bp_r += 0.22 * (lp_r - bp_r)
        sub_phase += 2.0 * math.pi * (85.0 + 55.0 * norm) / SAMPLE_RATE
        sub = 0.035 * math.sin(sub_phase)
        l_val = bell * (0.14 * bp_l + sub)
        r_val = bell * (0.14 * bp_r + sub)
        samples.append((l_val, r_val))
    return samples


def synth_chime():
    """Warm two-to-three note glass chime for reveal moments (480 ms)."""
    dur = 0.480
    n = int(SAMPLE_RATE * dur)
    notes = [
        (0.000, 1046.50, 0.12),   # C6
        (0.055, 1318.51, 0.11),   # E6
        (0.110, 1567.98, 0.09),   # G6
    ]
    samples = []
    for i in range(n):
        t = i / SAMPLE_RATE
        l_val = 0.0
        r_val = 0.0
        for idx, (start, freq, amp) in enumerate(notes):
            dt = t - start
            if dt < 0.0:
                continue
            atk = min(1.0, dt / 0.008)
            env = math.exp(-dt * 7.8)
            tone = atk * env * amp * (
                math.sin(2.0 * math.pi * freq * dt)
                + 0.18 * math.sin(2.0 * math.pi * freq * 2.0 * dt) * math.exp(-dt * 12.0)
            )
            pan = 0.45 + 0.05 * idx
            l_val += tone * (1.0 - pan * 0.25)
            r_val += tone * (0.75 + pan * 0.25)
        samples.append((l_val, r_val))
    return samples


def synth_transition():
    """Gentle curtain / page transition tone (200 ms)."""
    dur = 0.200
    n = int(SAMPLE_RATE * dur)
    samples = []
    phase = 0.0
    for i in range(n):
        t = i / SAMPLE_RATE
        norm = t / dur
        bell = math.sin(math.pi * norm) ** 1.5
        phase += 2.0 * math.pi * (210.0 + 70.0 * norm) / SAMPLE_RATE
        val = bell * 0.075 * math.sin(phase)
        samples.append((val, val))
    return samples


def build_sfx_track(events_data, durations_data):
    sfx_bank = {
        "click": synth_click(),
        "select": synth_select(),
        "whoosh": synth_whoosh(),
        "chime": synth_chime(),
        "transition": synth_transition(),
    }

    dur_by_clip = {c["clip"]: c for c in durations_data["clips"]}
    total_dur_s = sum(c["actualDur"] for c in durations_data["clips"])
    total_samples = int(math.ceil(total_dur_s * SAMPLE_RATE)) + SAMPLE_RATE // 4

    buf_l = [0.0] * total_samples
    buf_r = [0.0] * total_samples

    cum_ms = 0.0
    placed_count = 0

    for clip_entry in events_data["clips"]:
        name = clip_entry["clip"]
        dur_info = dur_by_clip[name]
        actual_ms = dur_info["actualDur"] * 1000.0
        speed_ratio = dur_info["speedRatio"]

        for ev in clip_entry.get("events", []):
            ev_type = ev["type"]
            wave_data = sfx_bank.get(ev_type)
            if not wave_data:
                continue
            local_ms = max(0.0, min(ev["timeMs"] * speed_ratio, actual_ms - 15.0))
            global_ms = cum_ms + local_ms
            start_idx = int((global_ms / 1000.0) * SAMPLE_RATE)
            for offset, (sl, sr) in enumerate(wave_data):
                idx = start_idx + offset
                if 0 <= idx < total_samples:
                    buf_l[idx] += sl
                    buf_r[idx] += sr
            placed_count += 1

        cum_ms += actual_ms

    os.makedirs(os.path.dirname(WAV_OUT_PATH), exist_ok=True)
    with wave.open(WAV_OUT_PATH, "wb") as wf:
        wf.setnchannels(CHANNELS)
        wf.setsampwidth(2)
        wf.setframerate(SAMPLE_RATE)
        frames = bytearray()
        for i in range(total_samples):
            sl = int(max(-32767, min(32767, math.tanh(buf_l[i]) * 32767.0)))
            sr = int(max(-32767, min(32767, math.tanh(buf_r[i]) * 32767.0)))
            frames.extend(struct.pack("<hh", sl, sr))
        wf.writeframes(bytes(frames))

    print(
        f"Synthesized {placed_count} SFX cues across {total_dur_s:.2f}s -> {WAV_OUT_PATH}"
    )


def mux_final_video():
    if not os.path.exists(MERGED_VIDEO_PATH):
        raise FileNotFoundError(f"Merged video not found: {MERGED_VIDEO_PATH}")

    cmd = [
        "ffmpeg", "-y",
        "-i", MERGED_VIDEO_PATH,
        "-i", WAV_OUT_PATH,
        "-c:v", "libx264",
        "-crf", "21",
        "-preset", "medium",
        "-pix_fmt", "yuv420p",
        "-r", "30",
        "-c:a", "aac",
        "-b:a", "192k",
        "-movflags", "+faststart",
        "-shortest",
        FINAL_MP4_PATH,
    ]
    print("Muxing final MP4 with H.264 + AAC SFX...")
    subprocess.run(cmd, check=True)
    size_mb = os.path.getsize(FINAL_MP4_PATH) / (1024 * 1024)
    print(f"Final video saved: {FINAL_MP4_PATH} ({size_mb:.2f} MB)")


def main():
    with open(EVENTS_PATH, encoding="utf-8") as f:
        events_data = json.load(f)
    with open(DURATIONS_PATH, encoding="utf-8") as f:
        durations_data = json.load(f)

    build_sfx_track(events_data, durations_data)
    mux_final_video()


if __name__ == "__main__":
    main()
