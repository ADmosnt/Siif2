<!-- resources/js/components/CubEgg.vue -->
<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import * as THREE from 'three';
import { gsap } from 'gsap'; // ¡La herramienta correcta para animaciones!

// --- Estado y Refs ---
const canvasRef = ref(null);
const hasInteracted = ref(false); // El audio requiere interacción del usuario

// Variables de Three.js que necesitamos mantener
let renderer, scene, camera, cube, listener, sound;
let animationFrameId;
let isAutoRotating = true;
let currentFaceIndex = 0;

// Las rotaciones objetivo para cada cara (en radianes, como debe ser)
const faceRotations = [
    { x: 0, y: 0 }, // Frontal
    { x: 0, y: Math.PI / 2 }, // Derecha
    { x: 0, y: Math.PI }, // Trasera
    { x: 0, y: -Math.PI / 2 }, // Izquierda
    { x: -Math.PI / 2, y: 0 }, // Superior
    { x: Math.PI / 2, y: 0 } // Inferior
];

// --- Ciclo de Vida ---
onMounted(() => {
  initScene();
  initEventListeners();
  animate();
});

onUnmounted(() => {
  // Limpieza. No dejes basura en memoria.
  cancelAnimationFrame(animationFrameId);
  window.removeEventListener('keydown', handleKeyDown);
  if (sound && sound.isPlaying) sound.stop();
  renderer.dispose();
});

// --- Inicialización ---
const initScene = () => {
  // 1. Escena y Cámara
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
  camera.position.z = 3;

  // 2. Renderizador con FONDO TRANSPARENTE. Esto es lo que querías.
  renderer = new THREE.WebGLRenderer({
    canvas: canvasRef.value,
    alpha: true, // ¡CLAVE!
    antialias: true
  });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

  // 3. Luces. Sin luces, los materiales estándar se ven negros.
  const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
  scene.add(ambientLight);
  const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
  directionalLight.position.set(1, 2, 3);
  scene.add(directionalLight);

  // 4. El Cubo (La forma correcta)
  const textureLoader = new THREE.TextureLoader();
  const textureFiles = [
    '/textures/cara1.png', '/textures/cara2.png', '/textures/cara3.png',
    '/textures/cara4.png', '/textures/cara5.png', '/textures/cara6.png'
  ];
  
  // Se crea un array de materiales. Uno para cada cara.
  // Esto es mucho más limpio que tu bucle de dibujo.
  const materials = textureFiles.map(file => {
      const texture = textureLoader.load(file);
      return new THREE.MeshStandardMaterial({ map: texture });
  });

  const geometry = new THREE.BoxGeometry(1.5, 1.5, 1.5);
  cube = new THREE.Mesh(geometry, materials);
  scene.add(cube);

  // 5. El Audio (La forma sana)
  listener = new THREE.AudioListener();
  camera.add(listener);
  sound = new THREE.PositionalAudio(listener);
  const audioLoader = new THREE.AudioLoader();
  audioLoader.load('/sounds/audio.wav', (buffer) => {
    sound.setBuffer(buffer);
    sound.setLoop(true);
    sound.setVolume(0.5);
    // No reproducir hasta que el usuario haga clic.
  });
  cube.add(sound);
};

// --- Lógica de Animación y Controles ---
const animate = () => {
  animationFrameId = requestAnimationFrame(animate);

  if (isAutoRotating) {
    cube.rotation.y += 0.005;
    cube.rotation.x += 0.003;
  }

  renderer.render(scene, camera);
};

const handleKeyDown = (event) => {
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
        case ' ': // Espacio
            isAutoRotating = true;
            break;
        case 'm':
            toggleSound();
            break;
        case '=': // +
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
    // GSAP hace el trabajo sucio de la animación.
    // Anima las propiedades de rotación del cubo a los valores objetivo.
    gsap.to(cube.rotation, {
        duration: 0.75, // Duración de la animación
        x: faceRotations[currentFaceIndex].x,
        y: faceRotations[currentFaceIndex].y,
        ease: 'power2.out' // Una curva de aceleración agradable
    });
};

const changePlaybackSpeed = (delta) => {
    if (sound) {
        // ¿Ves? Una simple propiedad. Sin reiniciar nada.
        sound.playbackRate = Math.max(0.1, Math.min(3, sound.playbackRate + delta));
        console.log(`Velocidad de audio: ${sound.playbackRate.toFixed(2)}x`);
    }
};

const toggleSound = () => {
    if (sound && sound.isPlaying) {
        sound.pause();
    } else if (sound && !sound.isPlaying && hasInteracted.value) {
        sound.play();
    }
}

// Los navegadores bloquean el audio hasta que el usuario interactúa.
// Esta función maneja el primer clic.
const handleClickToPlay = () => {
  if (!hasInteracted.value) {
    hasInteracted.value = true;
    if (sound && !sound.isPlaying) {
      sound.play();
    }
  }
};

const initEventListeners = () => {
  window.addEventListener('keydown', handleKeyDown);
  // También maneja el redimensionamiento de la ventana
  window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  });
};
</script>


<template>
  <div class="easter-egg-container" @click="handleClickToPlay">
    <canvas ref="canvasRef"></canvas>
    <div v-if="!hasInteracted" class="interaction-overlay">
      Haz clic para iniciar el audio
    </div>
  </div>
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
  background-color: rgba(0, 0, 0, 0.5);
  color: white;
  font-size: 2rem;
  font-family: sans-serif;
  cursor: pointer;
  z-index: 10000;
}

canvas {
  display: block;
}
</style>