<!-- resources/js/components/LayoutComponents/CubEgg.vue -->
<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import * as THREE from 'three';
import { gsap } from 'gsap';

const emit = defineEmits(['close']);

const canvasRef = ref(null);
const hasInteracted = ref(false);
const isVisible = ref(true);

let renderer, scene, camera, cube, listener, sound;
let animationFrameId;
let resizeHandler;
let isAutoRotating = true;
let currentFaceIndex = 0;
let audioLoaded = false;

const faceRotations = [
    { x: 0, y: 0 },
    { x: 0, y: Math.PI / 2 },
    { x: 0, y: Math.PI },
    { x: 0, y: -Math.PI / 2 },
    { x: -Math.PI / 2, y: 0 },
    { x: Math.PI / 2, y: 0 }
];

onMounted(() => {
  initScene();
  initEventListeners();
  animate();
});

onUnmounted(() => {
  cleanup();
});

const cleanup = () => {
  cancelAnimationFrame(animationFrameId);
  window.removeEventListener('keydown', handleKeyDown);
  if (resizeHandler) window.removeEventListener('resize', resizeHandler);
  if (sound && sound.isPlaying) sound.stop();
  if (renderer) renderer.dispose();
  if (cube) {
    cube.geometry.dispose();
    if (Array.isArray(cube.material)) {
      cube.material.forEach(m => {
        if (m.map) m.map.dispose();
        m.dispose();
      });
    }
  }
};

const initScene = () => {
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
  camera.position.z = 3;

  renderer = new THREE.WebGLRenderer({
    canvas: canvasRef.value,
    alpha: true,
    antialias: true
  });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

  const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
  scene.add(ambientLight);
  const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
  directionalLight.position.set(1, 2, 3);
  scene.add(directionalLight);

  const textureLoader = new THREE.TextureLoader();
  const textureFiles = [
    '/textures/cara1.png', '/textures/cara2.png', '/textures/cara3.png',
    '/textures/cara4.png', '/textures/cara5.png', '/textures/cara6.png'
  ];

  const fallbackColors = [0x3498db, 0x2ecc71, 0xe74c3c, 0x9b59b6, 0xf1c40f, 0xe67e22];

  const materials = textureFiles.map((file, i) => {
      const texture = textureLoader.load(
        file,
        undefined,
        undefined,
        () => {
          // Si la textura falla, usar color sólido como fallback
          materials[i] = new THREE.MeshStandardMaterial({ color: fallbackColors[i] });
          if (cube) cube.material = materials;
        }
      );
      return new THREE.MeshStandardMaterial({ map: texture });
  });

  const geometry = new THREE.BoxGeometry(1.5, 1.5, 1.5);
  cube = new THREE.Mesh(geometry, materials);
  scene.add(cube);

  // Audio (opcional - no rompe si el archivo no existe)
  try {
    listener = new THREE.AudioListener();
    camera.add(listener);
    sound = new THREE.PositionalAudio(listener);
    const audioLoader = new THREE.AudioLoader();
    audioLoader.load(
      '/sounds/audio.wav',
      (buffer) => {
        sound.setBuffer(buffer);
        sound.setLoop(true);
        sound.setVolume(0.5);
        audioLoaded = true;
      },
      undefined,
      () => {
        // Audio no disponible, no es crítico
        audioLoaded = false;
      }
    );
    cube.add(sound);
  } catch {
    audioLoaded = false;
  }
};

const animate = () => {
  animationFrameId = requestAnimationFrame(animate);

  if (isAutoRotating && cube) {
    cube.rotation.y += 0.005;
    cube.rotation.x += 0.003;
  }

  if (renderer && scene && camera) {
    renderer.render(scene, camera);
  }
};

const handleKeyDown = (event) => {
    if (event.key === 'Escape') {
        emit('close');
        return;
    }

    isAutoRotating = false;

    switch (event.key) {
        case 'ArrowRight':
            currentFaceIndex = (currentFaceIndex + 1) % faceRotations.length;
            snapToFace();
            break;
        case 'ArrowLeft':
            currentFaceIndex = (currentFaceIndex - 1 + faceRotations.length) % faceRotations.length;
            snapToFace();
            break;
        case ' ':
            isAutoRotating = true;
            break;
        case 'm':
            toggleSound();
            break;
        case '=':
        case '+':
            changePlaybackSpeed(0.1);
            break;
        case '-':
        case '_':
            changePlaybackSpeed(-0.1);
            break;
    }
};

const snapToFace = () => {
    if (!cube) return;
    gsap.to(cube.rotation, {
        duration: 0.75,
        x: faceRotations[currentFaceIndex].x,
        y: faceRotations[currentFaceIndex].y,
        ease: 'power2.out'
    });
};

const changePlaybackSpeed = (delta) => {
    if (sound && audioLoaded) {
        sound.playbackRate = Math.max(0.1, Math.min(3, sound.playbackRate + delta));
    }
};

const toggleSound = () => {
    if (!audioLoaded) return;
    if (sound && sound.isPlaying) {
        sound.pause();
    } else if (sound && !sound.isPlaying && hasInteracted.value) {
        sound.play();
    }
};

const handleClickToPlay = () => {
  if (!hasInteracted.value) {
    hasInteracted.value = true;
    if (audioLoaded && sound && !sound.isPlaying) {
      sound.play();
    }
  }
};

const initEventListeners = () => {
  window.addEventListener('keydown', handleKeyDown);
  resizeHandler = () => {
    if (!camera || !renderer) return;
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  };
  window.addEventListener('resize', resizeHandler);
};
</script>


<template>
  <Transition name="fade">
    <div v-if="isVisible" class="easter-egg-container" @click="handleClickToPlay">
      <canvas ref="canvasRef"></canvas>
      <div v-if="!hasInteracted" class="interaction-overlay">
        <div class="overlay-content">
          <p class="overlay-title">Easter Egg Activado</p>
          <p class="overlay-subtitle">Haz clic para comenzar</p>
          <p class="overlay-hint">ESC para cerrar | Flechas para rotar | Espacio para auto-rotación | M para sonido</p>
        </div>
      </div>
      <button class="close-btn" @click.stop="emit('close')" title="Cerrar (ESC)">✕</button>
    </div>
  </Transition>
</template>


<style scoped>
.easter-egg-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 9999;
  outline: none;
  background: rgba(0, 0, 0, 0.3);
}

.interaction-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: rgba(0, 0, 0, 0.6);
  cursor: pointer;
  z-index: 10000;
}

.overlay-content {
  text-align: center;
  color: white;
  font-family: sans-serif;
}

.overlay-title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.overlay-subtitle {
  font-size: 1.2rem;
  opacity: 0.9;
  margin-bottom: 1rem;
}

.overlay-hint {
  font-size: 0.75rem;
  opacity: 0.6;
}

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 10001;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  font-size: 1.2rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

canvas {
  display: block;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>