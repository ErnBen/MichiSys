package com.snpp.MichiSys.repository;

import com.snpp.MichiSys.entity.Producto;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface ProductoRepository extends JpaRepository<Producto, Long> {

	// Buscar productos por nombre
	List<Producto> findByNombreContaining(String nombre);

	// Buscar productos con stock menor a un valor
	List<Producto> findByStockLessThan(Integer stock);

	// Nuevo Query Method
	List<Producto> findByActivoTrue();

}
