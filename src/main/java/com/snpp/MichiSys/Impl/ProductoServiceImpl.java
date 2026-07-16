package com.snpp.MichiSys.Impl;

import com.snpp.MichiSys.entity.Producto;
import com.snpp.MichiSys.repository.ProductoRepository;
import com.snpp.MichiSys.service.ProductoService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
public class ProductoServiceImpl implements ProductoService {

	@Autowired
	private ProductoRepository productoRepository;

	@Override
	public List<Producto> listar() {
		return productoRepository.findAll();
	}

	@Override
	public Producto buscarPorId(Long id) {
		return productoRepository.findById(id).orElse(null);
	}

	@Override
	public Producto guardar(Producto producto) {
		return productoRepository.save(producto);
	}

	@Override
	public Producto actualizar(Long id, Producto producto) {

		Producto productoActual = productoRepository.findById(id).orElse(null);

		if (productoActual != null) {

			productoActual.setNombre(producto.getNombre());
			productoActual.setDescripcion(producto.getDescripcion());
			productoActual.setPrecio(producto.getPrecio());
			productoActual.setStock(producto.getStock());
			productoActual.setActivo(producto.getActivo());
			productoActual.setCategoria(producto.getCategoria());

			return productoRepository.save(productoActual);
		}

		return null;
	}

	@Override
	public void eliminar(Long id) {
		productoRepository.deleteById(id);

	}

	@Override
	public List<Producto> listarActivos() {
		return productoRepository.findByActivoTrue();
	}
}
