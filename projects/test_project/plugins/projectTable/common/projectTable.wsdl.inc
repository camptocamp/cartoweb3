      <!-- ProjectTable -->

      <complexType name="ProjectTableRequest">
        <all>
          <element name="className" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ProjectTableResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="table" type="types:Table"/>
        </all>
      </complexType>
