      <!-- layers -->

      <complexType name="LayersRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layerIds" type="types:ArrayOfLayerId"/>
        </all>
      </complexType>

      <complexType name="LayersResult">
        <all>
          <element name="className" type="xsd:string"/>
        </all>
      </complexType>
