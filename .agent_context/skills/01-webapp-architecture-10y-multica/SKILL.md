---
name: 01-webapp-architecture-10y-multica
description: "Standar arsitektur web app senior 10+ tahun untuk Next.js, TypeScript, PostgreSQL, Prisma, security, dan scalability bertahap."
---

# Skill: Web App Architecture 10+ Tahun

Gunakan skill ini saat membuat PRD, SRS, SDD, struktur project, database, API, dan task breakdown.

## Default stack
- Next.js
- TypeScript
- TailwindCSS
- shadcn/ui
- PostgreSQL
- Prisma
- Zod
- Docker Compose
- GitHub
- VPS Ubuntu untuk staging/production awal

## Prinsip arsitektur
- Pisahkan UI, business logic, data access, config, dan integration.
- Validasi input di client dan server.
- Authorization wajib dicek server-side.
- Gunakan env variable untuk konfigurasi.
- Jangan menyimpan secret di repo.
- Buat domain model jelas.
- Hindari business logic tersebar di komponen UI.
- Buat README dan dokumentasi setup.
- Buat arsitektur yang bisa tumbuh bertahap.

## Deliverable wajib planning
- PRD
- SRS
- SDD
- UI/UX Flow
- Database Schema
- API Contract
- Task Breakdown
- ADR

## Prinsip MVP
- MVP harus kecil tapi bisa dipakai.
- Fitur nice-to-have ditunda.
- Fokus pada fitur yang menghasilkan value bisnis.
- Jangan memasukkan payment/AI/automation kompleks jika belum wajib.