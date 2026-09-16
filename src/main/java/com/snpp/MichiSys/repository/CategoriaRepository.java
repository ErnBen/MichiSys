package com.snpp.MichiSys.repository;

import com.snpp.MichiSys.entity.Categoria;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface CategoriaRepository extends JpaRepository<Categoria, Long> {

    // Buscar categorías por nombre
    List<Categoria> findByNombreContaining(String nombre);

}
