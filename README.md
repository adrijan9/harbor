# Harbor ⚓

> Multiple sites. One core. No ceremony.

Harbor is a minimal multi-project PHP runtime.

It is not an MVC framework.  
It does not use controllers.  
It does not enforce architecture.

Harbor provides:

- A simple router per project
- Direct PHP file execution
- A shared helper layer
- A structured way to host multiple sites in one codebase

That’s it.

---

## Philosophy

A harbor does not control ships.  
It provides structure, boundaries, and safety.

In Harbor:

- Each site is independent
- Each site has its own router
- Each request resolves directly to a PHP file
- Helpers provide shared functionality
- No forced patterns
- No service container magic
- No heavy abstraction layers

Harbor is built for clarity and control.

---

## What Harbor Is

- A multi-site PHP runtime
- A lightweight router layer
- A helper-driven structure
- A foundation for direct PHP development

---

## What Harbor Is Not

- Not MVC
- Not a full-stack framework
- Not opinionated
- Not dependency-heavy
- Not magic

If you want controllers, ORM layers, and dependency injection containers — Harbor is not that.

---

## Core Concept
/harbor  
/projects  
/site-one  
router.php  
index.php  
/site-two  
router.php  
index.php  
/helpers  
response.php  
request.php  
view.php  

Each project lives inside the harbor.  
Each project routes independently.  
All projects share the same foundation.

---

## Why Harbor?

Sometimes you need:

- Multiple small projects
- Shared infrastructure
- Direct control
- Simple structure
- No framework overhead

Harbor exists for that use case.

---

## Design Principles

- Keep it simple
- Keep it direct
- Keep it isolated
- Keep it predictable

Harbor does not try to be smart.  
It tries to stay out of your way.

---

## Tagline

**Harbor — Where your projects dock.**