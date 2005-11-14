      <!-- edit -->

      <complexType name="EditRequest">
        <all>
          <element name="shapes" type="types:ArrayOfShape"/>
          <element name="layer" type="types:String"/>
          <element name="objId" type="types:String"/>
        </all>
      </complexType>

      <complexType name="EditResult">
        <all>
		  <element name="shapeType" type="types:String"/>
        </all>
      </complexType>
