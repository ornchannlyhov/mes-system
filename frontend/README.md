# MES Frontend - Manufacturing Execution System UI

This is the user interface for the MES application, built with **Nuxt 4** (Vue.js 3 + TypeScript). It provides a modern, responsive dashboard for managing manufacturing operations.

## 🛠 Prerequisites

- **Node.js** (Latest LTS recommended, v18+)
- **NPM**, **Yarn**, or **PNPM**

## 🚀 Installation

1.  **Navigate to the frontend directory**:
    ```bash
    cd mes/frontend
    ```

2.  **Install dependencies**:
    ```bash
    npm install
    # or
    yarn install
    ```

3.  **Environment Configuration**:
    Create a `.env` file if necessary (check `.env.example` if available). Typically, you need to point to the backend API:
    ```bash
    NUXT_PUBLIC_API_BASE=http://localhost:8000/api
    ```

## 💻 Development

Start the development server with hot-module replacement (HMR):

```bash
npm run dev
```
The application will be accessible at `http://localhost:3000`.

## 📦 Production Build

To build the application for production deployment:

```bash
npm run build
```
The output will be in the `.output` directory. You can preview the production build locally:
```bash
npm run preview
```

## 📂 Project Structure

- **pages/**: Application routes and views (file-system based routing).
- **components/**: Reusable Vue components (UI kit, forms, charts).
    - `Ui/`: Generic UI components (Buttons, Modals, SlideOvers).
    - `common/`: Shared business components.
- **layouts/**: Page layouts (default, dashboard, auth).
- **stores/**: Pinia state management modules.
- **composables/**: Reusable logic functions (Vue hooks).
- **utils/**: Helper functions.

## ✨ Key Features

- **Nuxt 4**: Server-Side Rendering (SSR) capability and developer experience.
- **Slightly Modified Tailwind CSS**: For styling and design system.
- **Pinia**: State management.
- **TypeScript**: Full type safety.
- **Vue Chart.js**: For visualizing OEE and manufacturing metrics.
