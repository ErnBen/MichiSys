package com.snpp.MichiSys.controller;

import com.snpp.MichiSys.entity.Producto;
import com.snpp.MichiSys.service.ProductoService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/productos")
public class ProductoController {

	@Autowired
	private ProductoService productoService;

	@GetMapping
	public List<Producto> listar() {
		return productoService.listar();
	}

	@GetMapping("/{id}")
	public Producto buscarPorId(@PathVariable ("id") Long id) {
		return productoService.buscarPorId(id);
	}

	@PostMapping
	public Producto guardar(@RequestBody Producto producto) {
		return productoService.guardar(producto);
	}

	@PutMapping("/{id}")
	public Producto actualizar(@PathVariable ("id") Long id, @RequestBody Producto producto) {
		return productoService.actualizar(id, producto);
	}

	@DeleteMapping("/{id}")
	public void eliminar(@PathVariable  ("id") Long id) {
		productoService.eliminar(id);
	}

	@GetMapping("/activos")
	public List<Producto> listarActivos() {
		return productoService.listarActivos();
	}

}